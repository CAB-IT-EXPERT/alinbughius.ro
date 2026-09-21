from __future__ import annotations

import html
import re
from email import policy
from email.parser import BytesParser
from pathlib import Path

from PIL import Image, ImageDraw, ImageFont


ROOT = Path(__file__).resolve().parents[1]
ASSETS = ROOT / ".runtime" / "manual-assets"
MAIL_DIR = ROOT / ".runtime" / "test-export-suite"
LOGO_SOURCE = Path(r"D:\Downloads from Chrome\ChatGPT Image 15 iun. 2026, 14_46_22.png")

GREEN = "#0F4B3F"
TEAL = "#00A88F"
MINT = "#DDF4EC"
INK = "#17231F"
MUTED = "#5E6B66"
LINE = "#D9E4DF"
WHITE = "#FFFFFF"


def font(size: int, bold: bool = False):
    name = "seguisb.ttf" if bold else "segoeui.ttf"
    path = Path(r"C:\Windows\Fonts") / name
    return ImageFont.truetype(str(path), size=size)


def sanitize_email(eml_path: Path, output: Path, title: str) -> None:
    message = BytesParser(policy=policy.default).parsebytes(eml_path.read_bytes())
    body = ""
    if message.is_multipart():
        for part in message.walk():
            if part.get_content_type() == "text/html":
                body = part.get_content()
                break
    elif message.get_content_type() == "text/html":
        body = message.get_content()
    if not body:
        body = f"<pre>{html.escape(message.get_body(preferencelist=('plain',)).get_content())}</pre>"
    body = re.sub(r'href=(\"|\')https?://[^\"\']+/confirmare\.php\?[^\"\']+\1', 'href="#"', body, flags=re.I)
    body = re.sub(r'https?://[^\s<\"\']+/confirmare\.php\?[^\s<\"\']+', '#', body, flags=re.I)
    body = body.replace("client-test@example.com", "client@exemplu.ro")
    body = body.replace("Client Test Local", "Andrei Popescu")
    wrapper = f"""<!doctype html><html lang='ro'><head><meta charset='utf-8'>
    <style>
      *{{box-sizing:border-box}} body{{margin:0;background:#eef3f0;font-family:Arial,sans-serif;color:#17231f}}
      .mail-shell{{width:1080px;margin:24px auto;background:white;border:1px solid #d9e4df;border-radius:20px;overflow:hidden;box-shadow:0 18px 55px rgba(15,75,63,.12)}}
      .mail-top{{background:#0f4b3f;color:white;padding:20px 28px;display:flex;justify-content:space-between;align-items:center}}
      .mail-top strong{{font-size:22px}} .mail-top span{{font-size:14px;color:#c9e8de}}
      .meta{{padding:18px 28px;border-bottom:1px solid #e4ece8;display:grid;grid-template-columns:80px 1fr;gap:6px 14px;font-size:14px}}
      .meta b{{color:#5e6b66}} .content{{padding:8px 24px 28px}} .content>table{{margin:auto!important}}
    </style></head><body><div class='mail-shell'>
      <div class='mail-top'><strong>{html.escape(title)}</strong><span>Exemplu demonstrativ</span></div>
      <div class='meta'><b>De la</b><span>{html.escape(str(message.get('From','contact@alinbughius.ro')))}</span>
      <b>Către</b><span>{html.escape(str(message.get('To','destinatar')))}</span>
      <b>Subiect</b><span>{html.escape(str(message.get('Subject',title)))}</span></div>
      <div class='content'>{body}</div></div></body></html>"""
    output.write_text(wrapper, encoding="utf-8")


def crop_logo() -> None:
    image = Image.open(LOGO_SOURCE).convert("RGB")
    # Keep the supplied CAB IT artwork, but crop the empty side space for a clean document mark.
    bbox = image.getbbox() or (0, 0, image.width, image.height)
    crop = image.crop(bbox)
    left = int(crop.width * 0.17)
    right = int(crop.width * 0.84)
    top = int(crop.height * 0.05)
    bottom = int(crop.height * 0.95)
    crop.crop((left, top, right, bottom)).save(ASSETS / "cab-it-logo-crop.png", quality=95)


def draw_arrow(draw: ImageDraw.ImageDraw, start: tuple[int, int], end: tuple[int, int], color: str = TEAL, width: int = 6) -> None:
    draw.line([start, end], fill=color, width=width)
    import math
    angle = math.atan2(end[1] - start[1], end[0] - start[0])
    size = 18
    p1 = (end[0] - size * math.cos(angle - 0.55), end[1] - size * math.sin(angle - 0.55))
    p2 = (end[0] - size * math.cos(angle + 0.55), end[1] - size * math.sin(angle + 0.55))
    draw.polygon([end, p1, p2], fill=color)


def flow_diagram() -> None:
    w, h = 1900, 760
    img = Image.new("RGB", (w, h), "#F5F8F6")
    draw = ImageDraw.Draw(img)
    draw.rounded_rectangle((2, 2, w - 3, h - 3), radius=32, outline=LINE, width=3, fill="#F8FBF9")
    draw.text((70, 50), "Traseul unei programări", font=font(46, True), fill=INK)
    draw.text((70, 112), "O singură acțiune a clientului actualizează calendarul, e-mailul și CRM-ul.", font=font(24), fill=MUTED)

    boxes = [
        (70, 230, 365, 510, "1", "Clientul", "Alege serviciul, data și ora pe site."),
        (450, 230, 745, 510, "2", "Calendarul", "Verifică disponibilitatea și reține intervalul."),
        (830, 230, 1125, 510, "3", "E-mailurile", "Clientul primește dovada; Alin primește cererea."),
        (1210, 230, 1505, 510, "4", "Confirmarea", "Alin confirmă din CRM sau din e-mail."),
        (1590, 230, 1830, 510, "5", "CRM sincronizat", "Starea devine Confirmată și clientul este anunțat."),
    ]
    for x1, y1, x2, y2, no, title, detail in boxes:
        draw.rounded_rectangle((x1, y1, x2, y2), radius=24, fill=WHITE, outline=LINE, width=3)
        draw.ellipse((x1 + 22, y1 + 22, x1 + 78, y1 + 78), fill=MINT)
        draw.text((x1 + 41, y1 + 50), no, anchor="mm", font=font(24, True), fill=GREEN)
        draw.text((x1 + 24, y1 + 106), title, font=font(29, True), fill=GREEN)
        words = detail.split()
        lines, current = [], ""
        for word in words:
            test = (current + " " + word).strip()
            if draw.textlength(test, font=font(21)) > (x2 - x1 - 48):
                lines.append(current); current = word
            else:
                current = test
        if current: lines.append(current)
        for idx, line in enumerate(lines[:5]):
            draw.text((x1 + 24, y1 + 160 + idx * 32), line, font=font(21), fill=MUTED)
    for i in range(4):
        draw_arrow(draw, (boxes[i][2] + 15, 370), (boxes[i + 1][0] - 15, 370))
    draw.rounded_rectangle((450, 590, 1505, 684), radius=20, fill=GREEN)
    draw.text((978, 637), "Confirmarea din e-mail și confirmarea din CRM au același rezultat.", anchor="mm", font=font(27, True), fill=WHITE)
    img.save(ASSETS / "flow-programare.png", quality=95)


def email_settings_diagram() -> None:
    w, h = 1700, 650
    img = Image.new("RGB", (w, h), "#F8FBF9")
    draw = ImageDraw.Draw(img)
    draw.rounded_rectangle((2, 2, w - 3, h - 3), radius=28, outline=LINE, width=3)
    draw.text((70, 52), "Cum circulă e-mailurile", font=font(44, True), fill=INK)
    draw.text((70, 112), "Mesajele pleacă din platformă și ajung automat la persoana potrivită.", font=font(23), fill=MUTED)
    entries = [
        (80, 230, 390, 500, "SITE", "Cerere nouă"),
        (690, 190, 1010, 410, "ALIN", "Detalii + buton privat de confirmare"),
        (1290, 190, 1610, 410, "CLIENT", "Înregistrare și confirmare finală"),
        (690, 470, 1010, 600, "CRM", "Stare, cost, încasare, note"),
    ]
    for x1, y1, x2, y2, title, detail in entries:
        draw.rounded_rectangle((x1, y1, x2, y2), radius=22, fill=WHITE, outline=LINE, width=3)
        draw.text(((x1 + x2) // 2, y1 + 55), title, anchor="mm", font=font(28, True), fill=GREEN)
        words = detail.split(); lines=[]; current=""
        for word in words:
            test=(current+" "+word).strip()
            if draw.textlength(test,font=font(20)) > x2-x1-45: lines.append(current); current=word
            else: current=test
        if current: lines.append(current)
        for idx,line in enumerate(lines): draw.text(((x1+x2)//2,y1+115+idx*30),line,anchor="mm",font=font(20),fill=MUTED)
    draw_arrow(draw, (390, 310), (680, 265)); draw_arrow(draw, (390, 390), (1280, 310))
    draw_arrow(draw, (850, 415), (850, 460)); draw_arrow(draw, (1015, 520), (1280, 380))
    img.save(ASSETS / "flow-email.png", quality=95)


def main() -> None:
    ASSETS.mkdir(parents=True, exist_ok=True)
    crop_logo()
    flow_diagram()
    email_settings_diagram()
    sanitize_email(MAIL_DIR / "d74d2d04760ed49b71d288804dc6751a-owner.eml", ASSETS / "email-owner.html", "Cerere nouă de programare")
    sanitize_email(MAIL_DIR / "d74d2d04760ed49b71d288804dc6751a-receipt.eml", ASSETS / "email-client-receipt.html", "Cererea ta a fost înregistrată")
    sanitize_email(MAIL_DIR / "d74d2d04760ed49b71d288804dc6751a-confirmed.eml", ASSETS / "email-client-confirmed.html", "Programarea ta este confirmată")
    print(ASSETS)


if __name__ == "__main__":
    main()
