"""Scoped, backed-up deployment to the verified Alin hosting directories."""
import ftplib
import hashlib
import json
import secrets
import ssl
import sys
import time
import urllib.request
from datetime import datetime
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
PUBLIC = '/alinbughius.ro'
PRIVATE = '/alinbughius-private'
STATE = ROOT / '.runtime/deploy-state.json'
SETTINGS = json.loads((ROOT / '.runtime/ftp-access.json').read_text(encoding='utf-8'))
BACKUP = ROOT / '.runtime/deploy-backup-20260920-205948/alinbughius.ro'
ftp = ftplib.FTP_TLS(context=ssl.create_default_context(), timeout=45)
known_directories = set()

def reconnect():
    ftp.close()
    ftp.connect('d108.dataserver.ro', SETTINGS['port'])
    ftp.login(SETTINGS['username'], SETTINGS['password'])
    ftp.prot_p()
    ftp.voidcmd('TYPE I')

def mkdir(path):
    if path in known_directories:
        return
    try:
        ftp.mkd(path)
    except ftplib.error_perm:
        original = ftp.pwd()
        ftp.cwd(path)
        ftp.cwd(original)
    known_directories.add(path)

def upload(path, content, private=False):
    for attempt in range(3):
        try:
            return upload_once(path, content, private)
        except (EOFError, OSError, ftplib.error_temp):
            if attempt == 2:
                raise
            time.sleep(1)
            reconnect()

def upload_once(path, content, private=False):
    import io
    assert path.startswith(PUBLIC + '/') or path.startswith(PRIVATE + '/')
    parent = path.rsplit('/', 1)[0]
    parts = parent.strip('/').split('/')
    for end in range(1, len(parts) + 1):
        mkdir('/' + '/'.join(parts[:end]))
    temporary = parent + '/.deploy-' + secrets.token_hex(8)
    ftp.storbinary('STOR ' + temporary, io.BytesIO(content), blocksize=262144)
    if ftp.size(temporary) != len(content):
        raise RuntimeError('Upload size verification failed: ' + path)
    ftp.sendcmd('SITE CHMOD ' + ('600' if private else '644') + ' ' + temporary)
    ftp.rename(temporary, path)

def probe(state):
    request = urllib.request.Request('https://alinbughius.ro/' + state['probe'], headers={'X-Deploy-Check':state['token']})
    with urllib.request.urlopen(request, timeout=40) as response:
        result = json.load(response)
    print('Production checks:', json.dumps(result))
    if not all([result.get('openssl'), result.get('storage_writable'), result.get('private_outside_document_root'), result.get('smtp_authenticated')]):
        raise RuntimeError('Hosting checks failed; public entry point not switched')
    if tuple(map(int, result['php'].split('.')[:2])) < (8, 2):
        raise RuntimeError('PHP 8.2+ required')

try:
    reconnect()
    mode = sys.argv[1]
    if mode == 'stage':
        assert (BACKUP / 'index.html').read_text() == '<h1>Hosting CAB-IT activ</h1>\n' or (BACKUP / 'index.html').read_text().strip() == '<h1>Hosting CAB-IT activ</h1>'
        names = {name for name, facts in ftp.mlsd('/')}
        if 'alinbughius-private' in names and not STATE.exists():
            raise RuntimeError('Private directory already exists and is not ours')
        state = json.loads(STATE.read_text()) if STATE.exists() else {'probe':'deploy-check-' + secrets.token_hex(12) + '.php', 'token':secrets.token_hex(32)}
        STATE.write_text(json.dumps(state), encoding='utf-8')
        mkdir(PRIVATE)
        ftp.sendcmd('SITE CHMOD 700 ' + PRIVATE)
        for directory in ['app', 'vendor', 'config']:
            for path in sorted((ROOT / directory).rglob('*')):
                if path.is_file():
                    upload(PRIVATE + '/' + path.relative_to(ROOT).as_posix(), path.read_bytes(), True)
        upload(PRIVATE + '/tools/retry-mail.php', (ROOT / 'tools/retry-mail.php').read_bytes(), True)
        upload(PRIVATE + '/.htaccess', b'Require all denied\n', True)
        mkdir(PRIVATE + '/storage')
        ftp.sendcmd('SITE CHMOD 700 ' + PRIVATE + '/storage')
        for path in sorted((ROOT / 'public').rglob('*')):
            relative = path.relative_to(ROOT / 'public').as_posix()
            if path.is_file() and relative not in ('index.php', '.htaccess'):
                upload(PUBLIC + '/' + relative, path.read_bytes())
                print('Uploaded:', relative, flush=True)
        probe_code = (ROOT / 'tools/deploy-probe.php').read_text(encoding='utf-8').replace('__DEPLOY_TOKEN_HASH__', hashlib.sha256(state['token'].encode()).hexdigest())
        upload(PUBLIC + '/' + state['probe'], probe_code.encode())
        probe(state)
        print('Staging complete. Main page not switched yet.')
    elif mode == 'publish':
        state = json.loads(STATE.read_text())
        probe(state)
        upload(PUBLIC + '/index.php', (ROOT / 'public/index.php').read_bytes())
        upload(PUBLIC + '/.htaccess', (ROOT / 'public/.htaccess').read_bytes())
        with urllib.request.urlopen('https://alinbughius.ro/', timeout=30) as response:
            html = response.read().decode()
            assert response.status == 200 and 'Masaj terapeutic<br>la domiciliu' in html and 'measurement.js' in html
        print('Published and main page verified: https://alinbughius.ro/')
    elif mode == 'remove-probe':
        state = json.loads(STATE.read_text())
        assert state['probe'].startswith('deploy-check-') and '/' not in state['probe']
        ftp.delete(PUBLIC + '/' + state['probe'])
        print('Removed the temporary diagnostic endpoint only.')
    elif mode == 'seed-demo-booking':
        booking_id = hashlib.sha256(b'alinbughius-demo-booking-2026-09-21').hexdigest()[:32]
        destination = PRIVATE + '/storage/' + booking_id + '.json'
        try:
            if ftp.size(destination):
                print('Demo booking already exists: #' + booking_id[:8].upper())
                sys.exit(0)
        except ftplib.error_perm:
            pass
        with urllib.request.urlopen('https://alinbughius.ro/api/availability.php?service=terapeutic', timeout=30) as response:
            availability = json.load(response)
        chosen = None
        for day in availability.get('days', []):
            if '12:00' in day.get('slots', []):
                chosen = (day['date'], '12:00')
                break
        if chosen is None:
            for day in availability.get('days', []):
                if day.get('slots'):
                    chosen = (day['date'], day['slots'][0])
                    break
        if chosen is None:
            raise RuntimeError('No available slot for the demo booking')
        now = datetime.now().astimezone().isoformat(timespec='seconds')
        record = {
            'name': 'Client Test — demonstrație',
            'email': 'contact@alinbughius.ro',
            'phone': '0773 919 071',
            'service_id': 'terapeutic',
            'service': 'Masaj terapeutic',
            'duration': '60 min',
            'duration_minutes': 60,
            'buffer_minutes': 15,
            'plan': 'single',
            'sessions': 1,
            'price': 200,
            'travel_per_visit': 0,
            'total': 200,
            'amount': 200,
            'payment_status': 'unpaid',
            'zone': 'București, Sector 2',
            'date': chosen[0],
            'time': chosen[1],
            'privacy_version': '2026-09-21',
            'id': booking_id,
            'created_at': now,
            'updated_at': now,
            'status': 'pending',
            'source': 'manual',
            'manage_token': secrets.token_hex(32),
            'delivery': {'owner': now, 'receipt': now},
            'internal_notes': 'Programare demonstrativă creată la cerere. Poate fi confirmată, reprogramată sau anulată din CRM.',
        }
        upload(destination, json.dumps(record, ensure_ascii=False, separators=(',', ':')).encode('utf-8'), True)
        print(f"Created pending demo booking #{booking_id[:8].upper()} for {chosen[0]} at {chosen[1]}. No email sent.")
    elif mode == 'remove-demo-booking':
        import io
        booking_id = hashlib.sha256(b'alinbughius-demo-booking-2026-09-21').hexdigest()[:32]
        destination = PRIVATE + '/storage/' + booking_id + '.json'
        payload = io.BytesIO()
        ftp.retrbinary('RETR ' + destination, payload.write)
        record = json.loads(payload.getvalue().decode('utf-8'))
        if record.get('id') != booking_id or record.get('name') != 'Client Test — demonstrație':
            raise RuntimeError('Refusing to remove a record that is not the exact demo booking')
        ftp.delete(destination)
        print(f"Removed demo booking #{booking_id[:8].upper()} only.")
    else:
        raise RuntimeError('Choose stage, publish, remove-probe, seed-demo-booking or remove-demo-booking')
finally:
    ftp.close()
