from __future__ import annotations

from pathlib import Path

from docx import Document
from docx.enum.section import WD_SECTION
from docx.enum.table import WD_CELL_VERTICAL_ALIGNMENT, WD_TABLE_ALIGNMENT
from docx.enum.text import WD_ALIGN_PARAGRAPH, WD_BREAK
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
from docx.shared import Cm, Inches, Pt, RGBColor


ROOT = Path(__file__).resolve().parents[1]
ASSETS = ROOT / ".runtime" / "manual-assets"
OUT_DIR = ROOT / "outputs" / "manual-utilizare-cab-it-expert"
OUT_DOCX = OUT_DIR / "Manual-utilizare-programari-CRM-email-Alin-Bughius.docx"

GREEN = "0F4B3F"
TEAL = "009A86"
MINT = "DDF4EC"
LIGHT = "F4F8F5"
INK = "17231F"
MUTED = "5E6B66"
LINE = "D9E4DF"
WHITE = "FFFFFF"
GOLD = "C9983D"
RED = "B04B40"

_PENDING_PAGE_BREAK = False


def shade(cell, fill: str):
    tc_pr = cell._tc.get_or_add_tcPr()
    shd = tc_pr.find(qn("w:shd"))
    if shd is None:
        shd = OxmlElement("w:shd")
        tc_pr.append(shd)
    shd.set(qn("w:fill"), fill)


def borders(cell, color=LINE, size="6"):
    tc_pr = cell._tc.get_or_add_tcPr()
    tc_borders = tc_pr.find(qn("w:tcBorders"))
    if tc_borders is None:
        tc_borders = OxmlElement("w:tcBorders")
        tc_pr.append(tc_borders)
    for edge in ("top", "left", "bottom", "right", "insideH", "insideV"):
        element = tc_borders.find(qn(f"w:{edge}"))
        if element is None:
            element = OxmlElement(f"w:{edge}")
            tc_borders.append(element)
        element.set(qn("w:val"), "single")
        element.set(qn("w:sz"), size)
        element.set(qn("w:color"), color)


def no_borders(table):
    tbl_pr = table._tbl.tblPr
    tbl_borders = tbl_pr.find(qn("w:tblBorders"))
    if tbl_borders is None:
        tbl_borders = OxmlElement("w:tblBorders")
        tbl_pr.append(tbl_borders)
    for edge in ("top", "left", "bottom", "right", "insideH", "insideV"):
        element = OxmlElement(f"w:{edge}")
        element.set(qn("w:val"), "nil")
        tbl_borders.append(element)


def set_cell_margin(cell, top=100, start=120, bottom=100, end=120):
    tc = cell._tc
    tc_pr = tc.get_or_add_tcPr()
    tc_mar = tc_pr.first_child_found_in("w:tcMar")
    if tc_mar is None:
        tc_mar = OxmlElement("w:tcMar")
        tc_pr.append(tc_mar)
    for m, v in (("top", top), ("start", start), ("bottom", bottom), ("end", end)):
        node = tc_mar.find(qn(f"w:{m}"))
        if node is None:
            node = OxmlElement(f"w:{m}")
            tc_mar.append(node)
        node.set(qn("w:w"), str(v))
        node.set(qn("w:type"), "dxa")


def add_field(run, field):
    begin = OxmlElement("w:fldChar")
    begin.set(qn("w:fldCharType"), "begin")
    instr = OxmlElement("w:instrText")
    instr.set(qn("xml:space"), "preserve")
    instr.text = field
    separate = OxmlElement("w:fldChar")
    separate.set(qn("w:fldCharType"), "separate")
    text = OxmlElement("w:t")
    text.text = "1"
    end = OxmlElement("w:fldChar")
    end.set(qn("w:fldCharType"), "end")
    run._r.extend([begin, instr, separate, text, end])


def set_repeat_table_header(row):
    tr_pr = row._tr.get_or_add_trPr()
    repeat = OxmlElement("w:tblHeader")
    repeat.set(qn("w:val"), "true")
    tr_pr.append(repeat)


def add_hyperlink(paragraph, text, url, color=TEAL, underline=True):
    part = paragraph.part
    rid = part.relate_to(url, "http://schemas.openxmlformats.org/officeDocument/2006/relationships/hyperlink", is_external=True)
    hyperlink = OxmlElement("w:hyperlink")
    hyperlink.set(qn("r:id"), rid)
    new_run = OxmlElement("w:r")
    r_pr = OxmlElement("w:rPr")
    c = OxmlElement("w:color"); c.set(qn("w:val"), color); r_pr.append(c)
    if underline:
        u = OxmlElement("w:u"); u.set(qn("w:val"), "single"); r_pr.append(u)
    new_run.append(r_pr)
    t = OxmlElement("w:t"); t.text = text; new_run.append(t)
    hyperlink.append(new_run)
    paragraph._p.append(hyperlink)
    return hyperlink


def configure_document(doc: Document):
    section = doc.sections[0]
    section.page_width = Inches(8.5)
    section.page_height = Inches(11)
    section.top_margin = Inches(0.62)
    section.bottom_margin = Inches(0.62)
    section.left_margin = Inches(0.68)
    section.right_margin = Inches(0.68)
    section.header_distance = Inches(0.2)
    section.footer_distance = Inches(0.2)
    section.different_first_page_header_footer = False
    doc.settings.odd_and_even_pages_header_footer = False

    styles = doc.styles
    normal = styles["Normal"]
    normal.font.name = "Aptos"
    normal.font.size = Pt(9.5)
    normal.font.color.rgb = RGBColor.from_string(INK)
    normal.paragraph_format.space_after = Pt(5)
    normal.paragraph_format.line_spacing = 1.1
    for name, size, color in (("Title", 36, INK), ("Heading 1", 25, INK), ("Heading 2", 15, GREEN), ("Heading 3", 11, TEAL)):
        style = styles[name]
        style.font.name = "Aptos Display" if name != "Heading 3" else "Aptos"
        style.font.size = Pt(size)
        style.font.bold = name == "Heading 3"
        style.font.color.rgb = RGBColor.from_string(color)
        style.paragraph_format.keep_with_next = True
        style.paragraph_format.space_before = Pt(4 if name == "Heading 1" else 7)
        style.paragraph_format.space_after = Pt(6)
    styles["Caption"].font.name = "Aptos"
    styles["Caption"].font.size = Pt(8)
    styles["Caption"].font.italic = True
    styles["Caption"].font.color.rgb = RGBColor.from_string(MUTED)
    styles["Caption"].paragraph_format.space_after = Pt(6)

def add_page_title(doc, no: str, title: str, subtitle: str | None = None):
    global _PENDING_PAGE_BREAK
    p = doc.add_paragraph()
    if _PENDING_PAGE_BREAK:
        p.paragraph_format.page_break_before = True
        _PENDING_PAGE_BREAK = False
    p.paragraph_format.space_after = Pt(2)
    r = p.add_run("CAB IT EXPERT   |   " + no.upper())
    r.bold = True; r.font.size = Pt(8); r.font.color.rgb = RGBColor.from_string(TEAL)
    r.font.letter_spacing = Pt(1.2) if hasattr(r.font, "letter_spacing") else None
    h = doc.add_heading(title, level=1)
    h.alignment = WD_ALIGN_PARAGRAPH.LEFT
    h.paragraph_format.left_indent = Inches(0)
    h.paragraph_format.right_indent = Inches(0)
    if len(title) >= 42:
        for run in h.runs:
            run.font.size = Pt(22)
    if len(title) >= 54:
        for run in h.runs:
            run.font.size = Pt(20)
    h.paragraph_format.space_after = Pt(3)
    if subtitle:
        p = doc.add_paragraph(subtitle)
        p.paragraph_format.space_after = Pt(9)
        p.paragraph_format.line_spacing = 1.05
        for run in p.runs:
            run.font.size = Pt(10)
            run.font.color.rgb = RGBColor.from_string(MUTED)


def add_section_title(doc, title: str):
    doc.add_heading(title, level=2)


def add_rule(doc):
    p = doc.add_paragraph()
    p.paragraph_format.space_before = Pt(1)
    p.paragraph_format.space_after = Pt(6)
    p_pr = p._p.get_or_add_pPr()
    pbdr = OxmlElement("w:pBdr")
    bottom = OxmlElement("w:bottom")
    bottom.set(qn("w:val"), "single"); bottom.set(qn("w:sz"), "5"); bottom.set(qn("w:color"), LINE)
    pbdr.append(bottom); p_pr.append(pbdr)


def add_steps(doc, steps, start=1, compact=False):
    table = doc.add_table(rows=0, cols=2)
    table.autofit = False
    table.columns[0].width = Inches(0.48)
    table.columns[1].width = Inches(6.55)
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    no_borders(table)
    for idx, step in enumerate(steps, start):
        row = table.add_row()
        row.cells[0].vertical_alignment = WD_CELL_VERTICAL_ALIGNMENT.TOP
        row.cells[1].vertical_alignment = WD_CELL_VERTICAL_ALIGNMENT.TOP
        p = row.cells[0].paragraphs[0]
        p.alignment = WD_ALIGN_PARAGRAPH.CENTER
        p.paragraph_format.space_after = Pt(1)
        run = p.add_run(str(idx))
        run.bold = True; run.font.size = Pt(9); run.font.color.rgb = RGBColor.from_string(WHITE)
        shade(row.cells[0], GREEN)
        set_cell_margin(row.cells[0], 70, 40, 70, 40)
        p = row.cells[1].paragraphs[0]
        p.paragraph_format.space_after = Pt(4 if compact else 7)
        p.paragraph_format.line_spacing = 1.05
        if isinstance(step, tuple):
            heading, body = step
            r = p.add_run(heading + ". ")
            r.bold = True; r.font.color.rgb = RGBColor.from_string(GREEN)
            p.add_run(body)
        else:
            p.add_run(step)
        set_cell_margin(row.cells[1], 45, 100, 45, 20)
    return table


def add_bullets(doc, items, color=None, compact=True):
    for item in items:
        p = doc.add_paragraph(style="List Bullet")
        p.paragraph_format.left_indent = Inches(0.22)
        p.paragraph_format.first_line_indent = Inches(-0.12)
        p.paragraph_format.space_after = Pt(2 if compact else 4)
        r = p.add_run(item)
        if color:
            r.font.color.rgb = RGBColor.from_string(color)


def add_key_value_table(doc, rows, widths=(2.1, 4.9), header=None):
    table = doc.add_table(rows=0, cols=2)
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    table.autofit = False
    table.columns[0].width = Inches(widths[0])
    table.columns[1].width = Inches(widths[1])
    if header:
        row = table.add_row()
        set_repeat_table_header(row)
        for idx, text in enumerate(header):
            cell = row.cells[idx]
            shade(cell, GREEN)
            set_cell_margin(cell, 90, 120, 90, 120)
            p = cell.paragraphs[0]
            r = p.add_run(text); r.bold = True; r.font.size = Pt(8.5); r.font.color.rgb = RGBColor.from_string(WHITE)
            borders(cell, GREEN)
    for i, (key, value) in enumerate(rows):
        row = table.add_row()
        for idx, text in enumerate((key, value)):
            cell = row.cells[idx]
            shade(cell, "FFFFFF" if i % 2 == 0 else LIGHT)
            set_cell_margin(cell, 85, 120, 85, 120)
            p = cell.paragraphs[0]
            r = p.add_run(str(text)); r.font.size = Pt(8.5)
            if idx == 0: r.bold = True; r.font.color.rgb = RGBColor.from_string(GREEN)
            borders(cell)
    doc.add_paragraph().paragraph_format.space_after = Pt(0)
    return table


def add_tip(doc, label, text):
    p = doc.add_paragraph()
    p.paragraph_format.space_before = Pt(4)
    p.paragraph_format.space_after = Pt(7)
    r = p.add_run(label.upper() + "  →  ")
    r.bold = True; r.font.size = Pt(8); r.font.color.rgb = RGBColor.from_string(TEAL)
    r = p.add_run(text)
    r.font.size = Pt(9); r.font.color.rgb = RGBColor.from_string(INK)


def add_picture(doc, filename, width=7.05, caption=None):
    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p.paragraph_format.space_before = Pt(2)
    p.paragraph_format.space_after = Pt(2)
    p.add_run().add_picture(str(ASSETS / filename), width=Inches(width))
    if caption:
        c = doc.add_paragraph(caption, style="Caption")
        c.alignment = WD_ALIGN_PARAGRAPH.CENTER


def add_two_column_text(doc, left_title, left_items, right_title, right_items):
    table = doc.add_table(rows=1, cols=2)
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    table.autofit = False
    table.columns[0].width = Inches(3.45)
    table.columns[1].width = Inches(3.45)
    no_borders(table)
    for cell, title, items in zip(table.rows[0].cells, (left_title, right_title), (left_items, right_items)):
        set_cell_margin(cell, 80, 100, 80, 100)
        h = cell.paragraphs[0]
        r = h.add_run(title); r.bold = True; r.font.size = Pt(11); r.font.color.rgb = RGBColor.from_string(GREEN)
        for item in items:
            p = cell.add_paragraph(style="List Bullet")
            p.paragraph_format.left_indent = Inches(0.2)
            p.paragraph_format.first_line_indent = Inches(-0.12)
            p.paragraph_format.space_after = Pt(3)
            p.add_run(item)
    return table


def page_break(doc):
    global _PENDING_PAGE_BREAK
    _PENDING_PAGE_BREAK = True


def build():
    OUT_DIR.mkdir(parents=True, exist_ok=True)
    doc = Document()
    configure_document(doc)
    doc.core_properties.title = "Manual de utilizare pentru programări, CRM și e-mail"
    doc.core_properties.subject = "Platforma alinbughius.ro"
    doc.core_properties.author = "Popescu Alexie, Fondator Cab IT Expert"
    doc.core_properties.last_modified_by = "CAB IT Expert"
    doc.core_properties.keywords = "alinbughius.ro, CRM, programări, e-mail, manual"
    doc.core_properties.comments = "Manual redactat și verificat de CAB IT Expert."

    # 1 — Cover
    spacer = doc.add_paragraph(); spacer.paragraph_format.space_after = Pt(6)
    p = doc.add_paragraph(); p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p.add_run().add_picture(str(ASSETS / "cab-it-logo-crop.png"), width=Inches(2.0))
    p = doc.add_paragraph(); p.alignment = WD_ALIGN_PARAGRAPH.CENTER; p.paragraph_format.space_after = Pt(4)
    r = p.add_run("CAB IT EXPERT"); r.bold = True; r.font.size = Pt(12); r.font.color.rgb = RGBColor.from_string(TEAL)
    p = doc.add_paragraph(); p.alignment = WD_ALIGN_PARAGRAPH.CENTER; p.paragraph_format.space_before = Pt(18)
    r = p.add_run("MANUAL DE UTILIZARE"); r.bold = True; r.font.size = Pt(10); r.font.color.rgb = RGBColor.from_string(GREEN)
    p = doc.add_paragraph(); p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p.paragraph_format.space_before = Pt(3); p.paragraph_format.space_after = Pt(7)
    r = p.add_run("Programări, CRM și e-mail")
    r.bold = True; r.font.name = "Aptos Display"; r.font.size = Pt(34); r.font.color.rgb = RGBColor.from_string(INK)
    p = doc.add_paragraph(); p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = p.add_run("Platforma alinbughius.ro")
    r.font.size = Pt(15); r.font.color.rgb = RGBColor.from_string(TEAL)
    doc.add_paragraph()
    table = doc.add_table(rows=1, cols=2); table.alignment = WD_TABLE_ALIGNMENT.CENTER; table.autofit = False
    table.columns[0].width = Inches(3.2); table.columns[1].width = Inches(3.2); no_borders(table)
    for cell in table.rows[0].cells: set_cell_margin(cell, 120, 180, 120, 180)
    p = table.cell(0,0).paragraphs[0]; r=p.add_run("REDACTAT DE"); r.bold=True; r.font.size=Pt(7.5); r.font.color.rgb=RGBColor.from_string(MUTED)
    p = table.cell(0,0).add_paragraph("Popescu Alexie"); p.runs[0].bold=True; p.runs[0].font.size=Pt(11); p.runs[0].font.color.rgb=RGBColor.from_string(GREEN)
    p = table.cell(0,0).add_paragraph("Fondator Cab IT Expert"); p.runs[0].font.size=Pt(8.5); p.runs[0].font.color.rgb=RGBColor.from_string(MUTED)
    p = table.cell(0,1).paragraphs[0]; r=p.add_run("EDIȚIE"); r.bold=True; r.font.size=Pt(7.5); r.font.color.rgb=RGBColor.from_string(MUTED)
    p = table.cell(0,1).add_paragraph("Versiunea 1.0"); p.runs[0].bold=True; p.runs[0].font.size=Pt(11); p.runs[0].font.color.rgb=RGBColor.from_string(GREEN)
    p = table.cell(0,1).add_paragraph("21 septembrie 2026"); p.runs[0].font.size=Pt(8.5); p.runs[0].font.color.rgb=RGBColor.from_string(MUTED)
    p = doc.add_paragraph(); p.alignment = WD_ALIGN_PARAGRAPH.CENTER; p.paragraph_format.space_before = Pt(24)
    r=p.add_run("Document destinat administratorului platformei. Datele demonstrative din capturi nu reprezintă clienți reali.")
    r.font.size=Pt(8); r.font.color.rgb=RGBColor.from_string(MUTED)

    # 2 — Contents
    page_break(doc)
    add_page_title(doc, "Ghid", "Cuprins", "Un parcurs complet, de la solicitarea clientului până la confirmarea și urmărirea programării.")
    contents = [
        ("01", "Pornire rapidă și acces", "3"), ("02", "Traseul programării", "4"),
        ("03", "Programarea făcută de client", "5"), ("04", "E-mailurile automate", "6"),
        ("05", "Accesarea CRM-ului", "7"), ("06", "Gestionarea unei programări", "8"),
        ("07", "Programare manuală", "9"), ("08", "Căutare, paginare și export", "10"),
        ("09", "Program și disponibilitate", "11"), ("10", "Servicii, durate și prețuri", "12"),
        ("11", "Securitate și e-mail destinatar", "13"), ("12", "Configurarea contului de e-mail", "14"),
        ("13", "Rutina zilnică și depanare", "15"), ("14", "Reguli de siguranță și suport", "16"),
    ]
    table = doc.add_table(rows=0, cols=3); table.alignment=WD_TABLE_ALIGNMENT.CENTER; table.autofit=False
    table.columns[0].width=Inches(.65); table.columns[1].width=Inches(5.45); table.columns[2].width=Inches(.8); no_borders(table)
    for no, title, page in contents:
        row=table.add_row();
        for cell in row.cells: set_cell_margin(cell, 70, 80, 70, 80)
        shade(row.cells[0], MINT)
        p=row.cells[0].paragraphs[0]; p.alignment=WD_ALIGN_PARAGRAPH.CENTER; r=p.add_run(no); r.bold=True; r.font.size=Pt(8); r.font.color.rgb=RGBColor.from_string(GREEN)
        p=row.cells[1].paragraphs[0]; r=p.add_run(title); r.font.size=Pt(9.5); r.bold=True if no in ("01","02","03","04","05","06") else False
        p=row.cells[2].paragraphs[0]; p.alignment=WD_ALIGN_PARAGRAPH.RIGHT; r=p.add_run(page); r.font.size=Pt(8); r.font.color.rgb=RGBColor.from_string(MUTED)
    add_tip(doc, "Important", "Manualul nu conține parole. Datele de acces și parola contului de e-mail se transmit separat și se păstrează confidențial.")

    # 3 — Quick start
    page_break(doc)
    add_page_title(doc, "01 · Pornire rapidă", "Cele trei adrese de care ai nevoie", "Poți lucra din telefon sau calculator. Pentru administrare folosește mereu o conexiune sigură.")
    add_key_value_table(doc, [
        ("Site public", "https://alinbughius.ro/ — aici clientul vede serviciile și solicită o programare."),
        ("Panou CRM", "https://alinbughius.ro/admin/ — aici gestionezi programările, programul, serviciile și securitatea."),
        ("E-mail profesional", "contact@alinbughius.ro — adresa din care pleacă mesajele platformei și pe care o poți folosi pentru răspunsuri."),
        ("E-mail notificări", "Se configurează din CRM → Securitate. Implicit, notificările administratorului ajung la adresa stabilită la predare."),
    ], header=("Resursă", "Rol"))
    add_section_title(doc, "Prima autentificare")
    add_steps(doc, [
        ("Deschide panoul", "Introdu în browser adresa alinbughius.ro/admin/."),
        ("Autentifică-te", "Folosește utilizatorul și parola primite separat. Nu le salva pe un dispozitiv public."),
        ("Opțional: Ține-mă minte", "Bifează numai pe telefonul sau calculatorul personal; accesul poate rămâne activ până la 90 de zile, inclusiv după restart."),
        ("Verifică indicatorul", "Mesajul „Calendar activ” confirmă că panoul este încărcat și pregătit."),
    ], compact=True)
    add_two_column_text(doc, "În CRM găsești", [
        "Privire de ansamblu: situația rapidă și încasări.",
        "Programări: clienți, stări, costuri și note.",
        "Program & disponibilitate: zile, ore și excepții.",
    ], "Regulă simplă", [
        "Cerere online = etichetă Din site.",
        "Programare introdusă de tine = etichetă Manual.",
        "E-mailul și CRM-ul indică aceeași stare a programării.",
    ])
    add_tip(doc, "Recomandare", "Salvează în browser doar adresa panoului, nu și parola, dacă dispozitivul este folosit de mai multe persoane.")

    # 4 — Flow
    page_break(doc)
    add_page_title(doc, "02 · Traseul programării", "De la alegerea orei la confirmare", "Platforma coordonează automat calendarul, CRM-ul și mesajele trimise prin e-mail.")
    add_picture(doc, "flow-programare.png", 6.62, "Fluxul complet al unei programări făcute pe site.")
    add_section_title(doc, "Ce se întâmplă în fundal")
    add_steps(doc, [
        ("Disponibilitatea este verificată", "Sunt afișate numai orele libere, calculate după program, durata serviciului, pauza și rezervările existente."),
        ("Intervalul este reținut", "După trimiterea formularului, ora nu mai poate fi aleasă de alt client cât timp programarea este activă."),
        ("Se creează înregistrarea CRM", "Programarea apare cu starea „În așteptare”, sursa „Din site” și încasarea „Neîncasat”."),
        ("Se trimit două e-mailuri", "Clientul primește dovada înregistrării, iar Alin primește detaliile și butonul privat de confirmare."),
        ("Confirmarea sincronizează totul", "Confirmarea din e-mail sau CRM schimbă aceeași înregistrare; clientul primește automat mesajul final."),
    ], compact=True)
    add_tip(doc, "Cheia fluxului", "Nu există două programări separate. E-mailul și CRM-ul sunt două căi de lucru asupra aceleiași înregistrări.")

    # 5 — Public form
    page_break(doc)
    add_page_title(doc, "03 · Programarea clientului", "Cum se completează formularul public", "Calendarul arată în timp real doar intervalele care pot fi rezervate.")
    add_picture(doc, "public-booking.png", 7.05, "Formularul public de programare — captură demonstrativă.")
    add_steps(doc, [
        ("Serviciu", "Clientul alege tipul de masaj; durata și prețul se actualizează automat."),
        ("Zi și oră", "Clientul selectează una dintre zilele și orele afișate ca disponibile."),
        ("Date de contact", "Numele, telefonul și adresa de e-mail sunt obligatorii pentru solicitările din site."),
        ("Zonă", "Se selectează sectorul sau Ilfov; taxa de deplasare, dacă se aplică, intră în estimare."),
        ("Acord și trimitere", "Clientul acceptă informarea privind datele și apasă „Trimite cererea de programare”."),
    ], compact=True)
    add_tip(doc, "Rezultat", "Clientul vede confirmarea de primire pe ecran și primește imediat e-mailul de înregistrare. Confirmarea finală vine după acțiunea lui Alin.")

    # 6 — Email automatic
    page_break(doc)
    add_page_title(doc, "04 · E-mailurile automate", "Ce primește Alin și ce primește clientul", "Mesajele sunt generate automat, dar confirmarea rămâne sub controlul administratorului.")
    add_picture(doc, "flow-email.png", 6.55, "Fluxul mesajelor dintre site, Alin, client și CRM.")
    add_two_column_text(doc, "E-mailul primit de Alin", [
        "Conține numele, telefonul, serviciul, data, ora și zona.",
        "Include un buton privat pentru gestionarea și confirmarea cererii.",
        "Nu trebuie redirecționat altor persoane.",
    ], "E-mailurile clientului", [
        "1. Cererea a fost înregistrată — confirmă că intervalul este reținut.",
        "2. Programarea este confirmată — este trimis după confirmarea lui Alin.",
        "3. Actualizare/anulare — apare când programarea este modificată sau anulată.",
    ])
    add_section_title(doc, "Confirmarea direct din e-mail")
    add_steps(doc, [
        ("Deschide mesajul", "Verifică expeditorul și datele programării."),
        ("Apasă butonul privat", "Se deschide pagina de administrare a cererii. Simplul fapt că ai deschis e-mailul nu confirmă programarea."),
        ("Confirmă", "Apasă butonul de confirmare din pagina deschisă."),
        ("Verifică rezultatul", "CRM-ul va afișa starea „Confirmată”, iar clientul va primi e-mailul final."),
    ], compact=True)
    add_tip(doc, "Dublă verificare", "Dacă apeși din nou linkul unei programări deja confirmate, pagina te informează că programarea este deja confirmată; nu se creează o duplicare.")

    # 7 — CRM access
    page_break(doc)
    add_page_title(doc, "05 · Accesarea CRM-ului", "Panoul central al activității", "Tot ce modifici aici se aplică imediat calendarului și programărilor.")
    add_picture(doc, "crm-bookings.png", 7.05, "Pagina Programări, cu date demonstrative și fără informații despre clienți reali.")
    add_section_title(doc, "Navigare")
    add_key_value_table(doc, [
        ("Privire de ansamblu", "Programările apropiate, total încasat și total de încasat."),
        ("Programări", "Lista completă, căutare, filtrare, paginare, programare manuală și export."),
        ("Program & disponibilitate", "Program săptămânal, reguli de rezervare și zile speciale."),
        ("Servicii", "Denumire, durată, pauză, preț, vizibilitate și servicii noi."),
        ("Securitate", "Schimbarea parolei și adresa la care primești cererile noi."),
    ], header=("Secțiune", "Ce controlezi"))
    add_tip(doc, "Pe telefon", "Meniul se deschide din butonul din partea de sus. Panourile și ferestrele sunt optimizate pentru atingere.")

    # 8 — Booking management
    page_break(doc)
    add_page_title(doc, "06 · Gestionarea unei programări", "Totul într-un singur panou", "Apasă pe orice programare pentru a deschide fișa completă.")
    add_picture(doc, "crm-booking-popup.png", 6.18, "Fișa unei programări manuale — exemplu demonstrativ.")
    add_two_column_text(doc, "Date și acțiuni", [
        "Sună, WhatsApp și E-mail pornesc contactul direct.",
        "Data și ora pot fi modificate, dacă noul interval este liber.",
        "Costul este completat implicit, dar poate fi corectat.",
        "Încasarea se marchează Încasat sau Neîncasat.",
    ], "Stările programării", [
        "În așteptare: cerere primită, neconfirmată.",
        "Confirmată: întâlnirea este acceptată.",
        "Finalizată: serviciul a avut loc.",
        "Anulată: intervalul este eliberat.",
    ])
    add_section_title(doc, "Procedura recomandată")
    add_steps(doc, [
        ("Verifică", "Compară numele, serviciul, data, ora, zona și costul."),
        ("Contactează", "Dacă este nevoie, folosește acțiunile rapide pentru clarificări."),
        ("Actualizează", "Alege starea, corectează data/ora sau costul și adaugă o notă internă."),
        ("Salvează", "Apasă „Salvează modificările”. Dacă programarea devine confirmată, clientul primește e-mailul de confirmare."),
    ], compact=True)
    add_tip(doc, "Anulare", "Poți anula oricând din fișă. Intervalul se eliberează, iar clientul este informat prin e-mail dacă există o adresă validă.")

    # 9 — Manual booking
    page_break(doc)
    add_page_title(doc, "07 · Programare manuală", "Înregistrează apelurile și mesajele primite direct", "Programările introduse de administrator ocupă calendarul la fel ca cele făcute pe site.")
    add_picture(doc, "crm-manual-popup.png", 7.05, "Fereastra „Programare manuală”.")
    add_steps(doc, [
        ("Deschide", "În Programări, apasă „Programare manuală”."),
        ("Completează clientul", "Numele și telefonul sunt obligatorii; e-mailul și zona/adresa sunt opționale."),
        ("Alege serviciul și data", "După selecție, lista orelor afișează numai intervalele libere."),
        ("Stabilește detaliile", "Ora și serviciul sunt obligatorii. Costul se completează din serviciu, dar poate fi editat."),
        ("Setează evidența", "Alege starea programării, Încasat/Neîncasat și adaugă eventuale note interne."),
        ("Salvează", "Înregistrarea primește eticheta „Manual” și intervalul devine indisponibil pentru site."),
    ], compact=True)
    add_tip(doc, "E-mail opțional", "Dacă nu completezi e-mailul, CRM-ul păstrează programarea, dar comunicarea cu clientul se face prin telefon sau WhatsApp.")

    # 10 — Search/export
    page_break(doc)
    add_page_title(doc, "08 · Căutare, paginare și export", "Găsește rapid și raportează profesionist", "Lista rămâne ușor de folosit chiar și când numărul programărilor crește.")
    add_section_title(doc, "Căutare inteligentă")
    p = doc.add_paragraph("Poți căuta după nume, telefon, e-mail, serviciu, dată, oră, cost, sursă, stare sau încasare. Exemple: ")
    for example in ("mâine", "22.09.2026", "200 lei", "12:00", "manual", "neîncasat"):
        r = p.add_run(f"  {example}  "); r.bold=True; r.font.color.rgb=RGBColor.from_string(TEAL)
    p = doc.add_paragraph("Folosește filtrul de stare pentru a restrânge rezultatele. Paginarea de sub listă mută afișarea între grupurile de programări.")
    p.paragraph_format.space_after=Pt(7)
    add_picture(doc, "crm-export-popup.png", 6.35, "Exportul permite perioade rapide sau un interval personalizat.")
    add_section_title(doc, "Raportul Excel")
    add_steps(doc, [
        ("Apasă Exportă", "Butonul se află lângă „Programare manuală”."),
        ("Alege perioada", "Astăzi, ieri, ultimele 7 zile, luna aceasta, luna trecută, 3/6/12/24 luni, anul trecut, toată perioada sau Personalizat."),
        ("Generează raportul", "Fișierul XLSX se descarcă automat și include programările manuale și cele din site."),
    ], compact=True)
    add_tip(doc, "Conținut", "Raportul are logo, rezumat, analize, formule, statistici, încasări, surse, stări și foaia completă de programări.")

    # 11 — Schedule
    page_break(doc)
    add_page_title(doc, "09 · Program și disponibilitate", "Controlează exact orele care apar clienților", "Disponibilitatea se recalculează pe baza programului, serviciilor și rezervărilor active.")
    add_picture(doc, "crm-schedule.png", 7.05, "Programul săptămânal și regulile calendarului.")
    add_two_column_text(doc, "Program recurent", [
        "Activează sau dezactivează fiecare zi.",
        "Setează ora de început și de sfârșit.",
        "Apasă „Salvează programul”.",
    ], "Reguli calendar", [
        "Programări vizibile în avans: câte zile poate vedea clientul.",
        "Timp minim înainte: cât de aproape poate fi făcută o rezervare.",
        "Interval de pornire: valoare + minute sau ore.",
    ])
    add_section_title(doc, "Zile speciale")
    add_steps(doc, [
        ("Adaugă o zi specială", "Folosește butonul dedicat pentru concedii sau program diferit."),
        ("Zi indisponibilă", "Alege această variantă pentru a bloca întreaga zi."),
        ("Program special", "Alege orele diferite care vor înlocui programul săptămânal pentru acea dată."),
        ("Salvează", "Excepția are prioritate și devine vizibilă imediat în calendarul public."),
    ], compact=True)
    add_tip(doc, "Calcul", "O rezervare blochează durata serviciului plus pauza de după. Exemplu: 60 minute + 15 minute pauză = 75 minute ocupate.")

    # 12 — Services
    page_break(doc)
    add_page_title(doc, "10 · Servicii, durate și prețuri", "Configurează ce poate rezerva clientul", "Fiecare serviciu influențează prețul afișat și timpul blocat în calendar.")
    add_picture(doc, "crm-services.png", 7.05, "Carduri compacte de servicii; extinde un card pentru editare.")
    add_steps(doc, [
        ("Extinde serviciul", "Apasă „Editează” pe cardul dorit."),
        ("Modifică", "Actualizează denumirea, durata, pauza după ședință sau prețul."),
        ("Activează/ascunde", "Comutatorul stabilește dacă serviciul poate fi ales în formularul public."),
        ("Salvează", "Apasă „Salvează modificările” pentru a actualiza imediat calendarul."),
        ("Adaugă un serviciu", "Folosește butonul „Adaugă serviciu”, completează câmpurile și confirmă."),
    ], compact=True)
    add_two_column_text(doc, "Durată", [
        "Timpul efectiv al ședinței.",
        "Apare clientului în formular.",
    ], "Pauză după", [
        "Timp tampon între clienți.",
        "Nu este afișat ca parte a masajului, dar blochează calendarul.",
    ])
    add_tip(doc, "Verificare", "După o modificare importantă, deschide formularul public într-o fereastră nouă și verifică serviciul, prețul și orele disponibile.")

    # 13 — Security
    page_break(doc)
    add_page_title(doc, "11 · Securitate și notificări", "Parola contului și destinația cererilor", "Secțiunea Securitate controlează accesul în CRM și adresa care primește cererile noi.")
    add_picture(doc, "crm-security.png", 7.05, "Pagina Securitate — schimbarea parolei și e-mailul pentru notificări.")
    add_section_title(doc, "Schimbarea parolei")
    add_steps(doc, [
        ("Introdu parola actuală", "Este parola folosită la autentificare."),
        ("Alege parola nouă", "Minimum 12 caractere, cu literă mare, literă mică și cifră."),
        ("Confirmă", "Introdu din nou parola și apasă „Schimbă parola”."),
    ], compact=True)
    add_section_title(doc, "Unde primești cererile noi")
    add_steps(doc, [
        ("Găsește panoul Notificări programări", "Acesta conține adresa curentă de destinație."),
        ("Scrie noua adresă", "Poate fi adresa profesională sau o altă adresă verificată și folosită zilnic."),
        ("Salvează", "Următoarele cereri online vor ajunge la noua adresă."),
    ], compact=True)
    add_tip(doc, "Ține-mă minte", "Opțiunea poate păstra autentificarea pe dispozitiv până la 90 de zile. Ieșirea din cont sau schimbarea parolei revocă accesul persistent.")

    # 14 — Email configuration
    page_break(doc)
    add_page_title(doc, "12 · Configurarea e-mailului", "Folosește contact@alinbughius.ro pe telefon și calculator", "Recomandare: IMAP păstrează mesajele sincronizate între toate dispozitivele.")
    add_key_value_table(doc, [
        ("Adresă / utilizator", "contact@alinbughius.ro"),
        ("Parolă", "Parola contului de e-mail, transmisă separat"),
        ("Server de intrare", "mail.alinbughius.ro"),
        ("IMAP securizat", "Port 993 · SSL/TLS activ · autentificare obligatorie"),
        ("POP3 securizat", "Port 995 · SSL/TLS activ · folosește numai dacă aplicația nu acceptă IMAP"),
        ("Server de ieșire", "mail.alinbughius.ro"),
        ("SMTP securizat", "Port 465 · SSL/TLS activ · autentificare obligatorie"),
    ], header=("Câmp", "Valoare"))
    add_section_title(doc, "Configurare în orice aplicație de e-mail")
    add_steps(doc, [
        ("Adaugă un cont", "Alege „Alt cont”, „Cont personal” sau „Configurare manuală”, apoi selectează IMAP."),
        ("Introdu identitatea", "Adresa și numele de utilizator sunt contact@alinbughius.ro; introdu parola primită separat."),
        ("Completează serverul de intrare", "mail.alinbughius.ro, port 993, securitate SSL/TLS, autentificare cu parolă."),
        ("Completează serverul de ieșire", "mail.alinbughius.ro, port 465, securitate SSL/TLS și autentificare activă."),
        ("Finalizează și testează", "Trimite un mesaj către o adresă proprie și răspunde la el pentru a verifica trimiterea și primirea."),
    ], compact=True)
    add_tip(doc, "Webmail", "Dacă folosești webmail-ul furnizorului de găzduire, intră prin adresa indicată în panoul de găzduire. Nu folosi linkuri primite din surse necunoscute.")

    # 15 — Daily routine/troubleshooting
    page_break(doc)
    add_page_title(doc, "13 · Rutina zilnică și depanare", "Un mod de lucru simplu și predictibil", "Câteva verificări constante păstrează agenda, clienții și încasările în ordine.")
    add_two_column_text(doc, "La începutul zilei", [
        "Deschide e-mailul și CRM-ul.",
        "Verifică programările În așteptare.",
        "Confirmă sau contactează clienții.",
        "Verifică agenda și eventualele modificări.",
    ], "După fiecare ședință", [
        "Schimbă starea în Finalizată.",
        "Marchează Încasat, dacă plata a fost primită.",
        "Actualizează costul, dacă a existat o diferență.",
        "Adaugă numai note operaționale necesare.",
    ])
    add_section_title(doc, "Situații frecvente")
    add_key_value_table(doc, [
        ("Nu a venit e-mailul", "Verifică Spam/Junk, conexiunea, adresa din Securitate și dacă programarea apare în CRM."),
        ("Clientul nu vede o oră", "Verifică programul zilei, excepțiile, durata + pauza și programările active care ocupă intervalul."),
        ("Linkul spune „deja confirmată”", "Nu este o eroare; programarea a fost confirmată anterior din CRM sau din e-mail."),
        ("Nu pot trimite din aplicația de e-mail", "Reverifică SMTP 465, SSL/TLS, autentificarea și utilizatorul complet contact@alinbughius.ro."),
        ("Nu mă pot autentifica", "Verifică tastarea parolei, dezactivează completarea automată greșită și folosește recuperarea prin persoana responsabilă."),
        ("Am anulat din greșeală", "Contactează clientul și creează o nouă programare manuală pe un interval liber."),
    ], header=("Situație", "Ce faci"))
    add_tip(doc, "Înainte de suport", "Notează data, ora, serviciul, starea afișată și dispozitivul folosit. Nu trimite niciodată parola prin capturi de ecran.")

    # 16 — Safety/support
    page_break(doc)
    add_page_title(doc, "14 · Siguranță și suport", "Protejează datele clienților și accesul în platformă", "CRM-ul conține date de contact și informații operaționale care trebuie tratate cu discreție.")
    add_section_title(doc, "Reguli obligatorii")
    add_bullets(doc, [
        "Nu transmite parola CRM sau parola e-mailului prin mesaje nesecurizate.",
        "Nu redirecționa linkul privat de confirmare din e-mailul administratorului.",
        "Ieși din cont pe dispozitive împrumutate sau comune și nu folosi „Ține-mă minte” pe acestea.",
        "Păstrează în notițele interne doar informațiile strict necesare administrării întâlnirii.",
        "Verifică destinatarul înainte de a trimite un e-mail și evită atașarea listelor cu date personale.",
        "Exporturile XLSX trebuie păstrate în spații protejate și șterse când nu mai sunt necesare.",
        "Actualizează parola dacă bănuiești că a fost văzută sau folosită de altcineva.",
    ], compact=False)
    add_section_title(doc, "Asistență tehnică")
    p = doc.add_paragraph("Pentru suport privind platforma, contactează CAB IT Expert. Include o descriere clară, pagina în care apare problema și o captură fără parole sau date sensibile.")
    p.paragraph_format.space_after=Pt(8)
    table = doc.add_table(rows=1, cols=2); table.alignment=WD_TABLE_ALIGNMENT.CENTER; table.autofit=False
    table.columns[0].width=Inches(1.25); table.columns[1].width=Inches(5.65)
    for cell in table.rows[0].cells: set_cell_margin(cell, 120, 140, 120, 140); borders(cell); shade(cell, LIGHT)
    p=table.cell(0,0).paragraphs[0]; p.alignment=WD_ALIGN_PARAGRAPH.CENTER; p.add_run().add_picture(str(ASSETS / "cab-it-logo-crop.png"), width=Inches(.72))
    c=table.cell(0,1)
    p=c.paragraphs[0]; r=p.add_run("Popescu Alexie"); r.bold=True; r.font.size=Pt(14); r.font.color.rgb=RGBColor.from_string(GREEN)
    p=c.add_paragraph("Fondator Cab IT Expert"); p.runs[0].font.size=Pt(9); p.runs[0].font.color.rgb=RGBColor.from_string(MUTED)
    p=c.add_paragraph(); add_hyperlink(p, "cab-it.ro", "https://cab-it.ro/")
    p=doc.add_paragraph(); p.alignment=WD_ALIGN_PARAGRAPH.CENTER; p.paragraph_format.space_before=Pt(18)
    r=p.add_run("Sfârșitul manualului"); r.bold=True; r.font.size=Pt(10); r.font.color.rgb=RGBColor.from_string(TEAL)
    p=doc.add_paragraph("Versiunea 1.0 · 21 septembrie 2026"); p.alignment=WD_ALIGN_PARAGRAPH.CENTER; p.runs[0].font.size=Pt(8); p.runs[0].font.color.rgb=RGBColor.from_string(MUTED)

    # Avoid trailing blank paragraphs expanding unexpectedly.
    doc.save(OUT_DOCX)
    print(OUT_DOCX)


if __name__ == "__main__":
    build()
