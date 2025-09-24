#!/usr/bin/env python3
"""Generate a printable PDF report from the Markdown summary."""
from __future__ import annotations

import textwrap
from pathlib import Path

ROOT = Path(__file__).resolve().parent
MD_PATH = ROOT / "report.md"
PDF_PATH = ROOT / "report.pdf"

HEADER_WIDTH = 84
LINES_PER_PAGE = 46
FONT_SIZE = 11
LINE_HEIGHT = 14
PAGE_HEIGHT = 792
START_Y = 770


def load_sections() -> list[str]:
    raw = MD_PATH.read_text(encoding="utf-8")
    paragraphs: list[str] = []
    buffer: list[str] = []
    for raw_line in raw.splitlines():
        line = raw_line.rstrip()
        if not line:
            if buffer:
                paragraphs.append(" ".join(buffer))
                buffer = []
            paragraphs.append("")
            continue
        if line.startswith("#"):
            level = len(line) - len(line.lstrip("#"))
            title = line[level:].strip()
            if buffer:
                paragraphs.append(" ".join(buffer))
                buffer = []
            if level == 1:
                paragraphs.append(title.upper())
            elif level == 2:
                paragraphs.append(title)
            else:
                paragraphs.append(f"{title}")
            continue
        if line.startswith("- "):
            if buffer:
                paragraphs.append(" ".join(buffer))
                buffer = []
            paragraphs.append(f"• {line[2:].strip()}")
            continue
        if line.startswith("|") and line.endswith("|"):
            # Render tables as simple text rows separated by tabs.
            cells = [cell.strip() for cell in line.strip("|").split("|")]
            if set(cells) == {"-" * len(cells[0])}:
                continue
            if buffer:
                paragraphs.append(" ".join(buffer))
                buffer = []
            paragraphs.append(" | ".join(cells))
            continue
        if line[0].isdigit() and line[1:3] == ". ":
            if buffer:
                paragraphs.append(" ".join(buffer))
                buffer = []
            paragraphs.append(line)
            continue
        buffer.append(line)
    if buffer:
        paragraphs.append(" ".join(buffer))
    return paragraphs


def wrap_paragraphs(paragraphs: list[str]) -> list[str]:
    lines: list[str] = []
    wrapper = textwrap.TextWrapper(width=HEADER_WIDTH, break_long_words=False, break_on_hyphens=False)
    for paragraph in paragraphs:
        if paragraph == "":
            lines.append("")
            continue
        if paragraph.isupper() and len(paragraph.split()) < 10:
            # Treat as heading
            lines.append(paragraph)
            lines.append("")
            continue
        if paragraph.startswith("• ") or paragraph[0].isdigit():
            wrapped = wrapper.wrap(paragraph)
            lines.extend(wrapped or [paragraph])
            continue
        wrapped = wrapper.wrap(paragraph)
        lines.extend(wrapped or [paragraph])
        lines.append("")
    return lines


def chunk_lines(lines: list[str], lines_per_page: int) -> list[list[str]]:
    pages: list[list[str]] = []
    current: list[str] = []
    for line in lines:
        if len(current) >= lines_per_page:
            pages.append(current)
            current = []
        current.append(line)
    if current:
        pages.append(current)
    return pages


def pdf_escape(text: str) -> str:
    return text.replace("\\", "\\\\").replace("(", "\\(").replace(")", "\\)")


def build_content_stream(lines: list[str]) -> str:
    instructions = ["BT", f"/F1 {FONT_SIZE} Tf", f"{LINE_HEIGHT} TL", f"72 {START_Y} Td"]
    first_line = True
    for line in lines:
        if first_line:
            if line:
                instructions.append(f"({pdf_escape(line)}) Tj")
            first_line = False
            continue
        if not line:
            instructions.append("T*")
            continue
        instructions.append("T*")
        instructions.append(f"({pdf_escape(line)}) Tj")
    instructions.append("ET")
    return "\n".join(instructions)


def write_pdf(pages: list[list[str]]) -> None:
    objects: list[str] = []
    catalog_obj = "<< /Type /Catalog /Pages 2 0 R >>"
    objects.append(catalog_obj)
    # Placeholder for /Pages, will overwrite later once we know kids
    objects.append("")
    font_obj = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>"
    objects.append(font_obj)

    page_objects: list[int] = []
    content_objects: list[int] = []

    for idx, page_lines in enumerate(pages):
        page_obj_num = len(objects) + 1
        content_obj_num = len(objects) + 2
        page_objects.append(page_obj_num)
        content_objects.append(content_obj_num)

        page_obj = (
            "<< /Type /Page /Parent 2 0 R "
            "/MediaBox [0 0 612 792] "
            "/Resources << /Font << /F1 3 0 R >> >> "
            f"/Contents {content_obj_num} 0 R >>"
        )
        objects.append(page_obj)

        content_stream = build_content_stream(page_lines)
        content_bytes = content_stream.encode("utf-8")
        content_obj = f"<< /Length {len(content_bytes)} >>\nstream\n{content_stream}\nendstream"
        objects.append(content_obj)

    kids = " ".join(f"{num} 0 R" for num in page_objects)
    pages_obj = f"<< /Type /Pages /Kids [{kids}] /Count {len(page_objects)} >>"
    objects[1] = pages_obj

    output_parts: list[bytes] = []
    output_parts.append(b"%PDF-1.4\n")
    xref_positions = [0]
    offset = len(output_parts[0])

    for obj_number, obj_content in enumerate(objects, start=1):
        obj_bytes = f"{obj_number} 0 obj\n{obj_content}\nendobj\n".encode("utf-8")
        xref_positions.append(offset)
        output_parts.append(obj_bytes)
        offset += len(obj_bytes)

    xref_start = offset
    count = len(objects) + 1
    xref_header = f"xref\n0 {count}\n0000000000 65535 f \n"
    output_parts.append(xref_header.encode("utf-8"))
    for position in xref_positions[1:]:
        output_parts.append(f"{position:010d} 00000 n \n".encode("utf-8"))
        offset += 20  # not used later but keeps parity if extended
    trailer = f"trailer\n<< /Size {count} /Root 1 0 R >>\nstartxref\n{xref_start}\n%%EOF"
    output_parts.append(trailer.encode("utf-8"))

    PDF_PATH.write_bytes(b"".join(output_parts))


def main() -> None:
    paragraphs = load_sections()
    wrapped_lines = wrap_paragraphs(paragraphs)
    pages = chunk_lines(wrapped_lines, LINES_PER_PAGE)
    write_pdf(pages)
    print(f"Generated {PDF_PATH.relative_to(ROOT)} with {len(pages)} page(s).")


if __name__ == "__main__":
    main()
