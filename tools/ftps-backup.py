"""Back up only the verified Alin domain, over certificate-verified FTPS."""
import ftplib
import json
import ssl
from datetime import datetime
from pathlib import Path

root = Path(__file__).resolve().parent.parent
settings = json.loads((root / '.runtime/ftp-access.json').read_text(encoding='utf-8'))
destination = root / '.runtime' / ('deploy-backup-' + datetime.now().strftime('%Y%m%d-%H%M%S'))
ftp = ftplib.FTP_TLS(context=ssl.create_default_context(), timeout=30)

def backup(remote, local):
    local.mkdir(parents=True, exist_ok=True)
    for name, facts in list(ftp.mlsd(remote)):
        if name in ('.', '..'):
            continue
        if '/' in name or '\\' in name:
            raise RuntimeError('Unsafe remote filename')
        target = remote + '/' + name
        if facts.get('type') == 'dir':
            backup(target, local / name)
        elif facts.get('type') == 'file':
            with (local / name).open('wb') as handle:
                ftp.retrbinary('RETR ' + target, handle.write)
            print('Saved:', target, facts.get('size'))
        else:
            raise RuntimeError('Unexpected remote entry: ' + target)

try:
    ftp.connect('d108.dataserver.ro', settings['port'])
    ftp.login(settings['username'], settings['password'])
    ftp.prot_p()
    backup('/alinbughius.ro', destination / 'alinbughius.ro')
    print('Backup complete:', destination)
finally:
    ftp.close()
