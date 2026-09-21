"""Read-only FTPS inventory. Credentials stay in ignored .runtime/ftp-access.json."""
import ftplib
import json
import ssl
import sys
from pathlib import Path

root = Path(__file__).resolve().parent.parent
settings = json.loads((root / '.runtime/ftp-access.json').read_text(encoding='utf-8'))
ftp = ftplib.FTP_TLS(context=ssl.create_default_context(), timeout=30)
try:
    # This canonical TLS name was verified to present the same certificate as
    # the supplied FTP endpoint. Both control and data channels verify TLS.
    ftp.connect('d108.dataserver.ro', settings['port'])
    ftp.login(settings['username'], settings['password'])
    ftp.prot_p()
    path = sys.argv[1] if len(sys.argv) > 1 else '.'
    ftp.cwd(path)
    print('Verified encrypted FTPS. Current directory:', ftp.pwd())
    try:
        for name, facts in ftp.mlsd():
            print(json.dumps({'name':name, 'type':facts.get('type'), 'size':facts.get('size'), 'modified':facts.get('modify')}, ensure_ascii=False))
    except ftplib.error_perm:
        ftp.retrlines('LIST')
finally:
    ftp.close()
