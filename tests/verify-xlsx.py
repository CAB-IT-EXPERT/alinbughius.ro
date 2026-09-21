from __future__ import annotations

import sys
import zipfile
from pathlib import Path
from xml.etree import ElementTree as ET


path = Path(sys.argv[1] if len(sys.argv) > 1 else ".runtime/http-report-test.xlsx")
assert path.is_file(), f"Raport lipsă: {path}"

with zipfile.ZipFile(path) as archive:
    assert archive.testzip() is None, "Arhiva XLSX este coruptă"
    names = set(archive.namelist())
    required = {
        "xl/workbook.xml",
        "xl/styles.xml",
        "xl/worksheets/sheet1.xml",
        "xl/worksheets/sheet2.xml",
        "xl/worksheets/sheet3.xml",
        "xl/drawings/drawing1.xml",
    }
    assert required <= names, f"Părți XLSX lipsă: {required - names}"
    for name in (item for item in names if item.endswith((".xml", ".rels"))):
        ET.fromstring(archive.read(name))
    workbook = archive.read("xl/workbook.xml").decode("utf-8")
    assert all(f'name="{sheet}"' in workbook for sheet in ("Rezumat", "Analize", "Programari"))
    data = archive.read("xl/worksheets/sheet3.xml").decode("utf-8")
    assert "Manual" in data and "Din site" in data
    assert "manage_token" not in data and "privacy_token" not in data
    assert "autoFilter" in data and "state=\"frozen\"" in data
    summary = archive.read("xl/worksheets/sheet1.xml").decode("utf-8")
    analysis = archive.read("xl/worksheets/sheet2.xml").decode("utf-8")
    assert summary.count("<f>") >= 4
    assert analysis.count("<f>") >= 9
    images = [name for name in names if name.startswith("xl/media/") and name.endswith(".png")]
    assert images and archive.read(images[0]).startswith(b"\x89PNG\r\n\x1a\n")

print(f"PASS: XLSX valid, cu logo, formule, filtre si foi de analiza ({path.stat().st_size} bytes).")
