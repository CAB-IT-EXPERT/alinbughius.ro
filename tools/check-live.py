"""Read-only deployment checks; never submit a real booking."""
import concurrent.futures
import json
import urllib.error
import urllib.request

class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *args):
        return None

opener = urllib.request.build_opener(NoRedirect)
checks = [
    ('http://alinbughius.ro/', 301, 'https://alinbughius.ro/'),
    ('http://www.alinbughius.ro/', 301, 'https://alinbughius.ro/'),
    ('https://www.alinbughius.ro/', 301, 'https://alinbughius.ro/'),
    ('http://www.alinbughius.ro/confidentialitate.php?check=1', 301, 'https://alinbughius.ro/confidentialitate.php?check=1'),
    ('https://alinbughius.ro/', 200, None),
    ('https://alinbughius.ro/index.html', 301, 'https://alinbughius.ro/'),
    ('https://alinbughius.ro/confidentialitate.php', 200, None),
    ('https://alinbughius.ro/config/local.php', 403, None),
    ('https://alinbughius.ro/storage/', 403, None),
    ('https://alinbughius.ro/app/', 403, None),
    ('https://alinbughius.ro/.user.ini', 403, None),
    ('https://alinbughius.ro/php.ini', 403, None),
    ('https://alinbughius.ro/_bootstrap.php', 404, None),
    ('https://alinbughius.ro/confirmare.php?id=invalid&token=invalid', 404, None),
    ('https://alinbughius.ro/api/booking.php', 405, None),
    ('https://alinbughius.ro/assets/measurement.js?v=2', 200, None),
    ('https://alinbughius.ro/assets/carousel.js?v=2', 200, None),
    ('https://alinbughius.ro/robots.txt', 200, None),
    ('https://alinbughius.ro/sitemap.xml', 200, None),
]

def check(item):
    url, status, location = item
    try:
        response = opener.open(url, timeout=25)
    except urllib.error.HTTPError as error:
        response = error
    assert response.code == status, (url, response.code, status)
    if location:
        assert response.headers.get('Location') == location, (url, response.headers.get('Location'))
    response.close()
    return f'PASS {status} {url}'

with concurrent.futures.ThreadPoolExecutor(max_workers=4) as executor:
    for line in executor.map(check, checks):
        print(line)
for filename in ['therapy', 'touch', 'ritual']:
    request = urllib.request.Request(f'https://alinbughius.ro/assets/videos/{filename}.mp4', headers={'Range':'bytes=0-1023'})
    with urllib.request.urlopen(request, timeout=25) as response:
        assert response.code == 206
        assert response.headers.get_content_type() == 'video/mp4'
        assert len(response.read()) == 1024
    print('PASS video byte range:', filename)
with urllib.request.urlopen('https://alinbughius.ro/api/token.php', timeout=25) as response:
    token = json.load(response)
    assert len(token['csrf']) == 64 and len(token['request_id']) == 32
    assert 'secure' in response.headers.get('Set-Cookie', '').lower()
    assert 'httponly' in response.headers.get('Set-Cookie', '').lower()
    assert 'googletagmanager' not in response.headers.get('Content-Security-Policy', '')
print('PASS secure booking token, no third-party scripts on private/API responses. No emails sent.')
