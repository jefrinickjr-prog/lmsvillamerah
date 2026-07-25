#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
Script untuk konversi Dokumentasi Arsitektur Markdown ke PDF
Menggunakan reportlab (lightweight alternative)
"""

import sys
import os
from pathlib import Path

try:
    from reportlab.lib.pagesizes import letter, A4
    from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
    from reportlab.lib.units import inch
    from reportlab.platypus import SimpleDocTemplate, Paragraph, Spacer, PageBreak, Table, TableStyle
    from reportlab.lib.enums import TA_CENTER, TA_LEFT, TA_JUSTIFY
    from reportlab.lib import colors
except ImportError:
    print("⚠️ Dependencies belum terinstall")
    print("Install dengan: pip install reportlab")
    sys.exit(1)




def create_pdf_from_markdown(md_file, pdf_output):
    """Buat PDF dari file Markdown menggunakan reportlab"""
    
    print(f"📄 Membaca file: {md_file}")
    
    with open(md_file, 'r', encoding='utf-8') as f:
        md_content = f.read()
    
    # Buat PDF document
    doc = SimpleDocTemplate(
        pdf_output,
        pagesize=A4,
        rightMargin=0.75*inch,
        leftMargin=0.75*inch,
        topMargin=1*inch,
        bottomMargin=0.75*inch,
        title="Dokumentasi Arsitektur Teknologi",
        author="E-Learning Bimbel Gambar Villa Merah"
    )
    
    # Container untuk elemen
    story = []
    
    # Dapatkan styles
    styles = getSampleStyleSheet()
    
    # Tambahkan custom styles
    title_style = ParagraphStyle(
        'CustomTitle',
        parent=styles['Heading1'],
        fontSize=24,
        textColor=colors.HexColor('#1a202c'),
        spaceAfter=12,
        alignment=TA_CENTER,
        fontName='Helvetica-Bold'
    )
    
    heading2_style = ParagraphStyle(
        'CustomHeading2',
        parent=styles['Heading2'],
        fontSize=14,
        textColor=colors.HexColor('#2d3748'),
        spaceAfter=10,
        spaceBefore=10,
        fontName='Helvetica-Bold'
    )
    
    heading3_style = ParagraphStyle(
        'CustomHeading3',
        parent=styles['Heading3'],
        fontSize=12,
        textColor=colors.HexColor('#4a5568'),
        spaceAfter=8,
        spaceBefore=8,
        fontName='Helvetica-Bold'
    )
    
    body_style = ParagraphStyle(
        'CustomBody',
        parent=styles['BodyText'],
        fontSize=10,
        alignment=TA_JUSTIFY,
        spaceAfter=10,
        leading=14
    )
    
    # Cover page
    story.append(Spacer(1, 1.5*inch))
    story.append(Paragraph("Dokumentasi Arsitektur Teknologi", title_style))
    story.append(Spacer(1, 0.2*inch))
    story.append(Paragraph("E-Learning Bimbel Gambar Villa Merah", styles['Normal']))
    story.append(Spacer(1, 0.8*inch))
    
    info_data = [
        ['Versi', '1.0'],
        ['Tanggal', '25 Juli 2026'],
        ['Status', 'Production Ready'],
    ]
    
    info_table = Table(info_data, colWidths=[2*inch, 3*inch])
    info_table.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (0, -1), colors.HexColor('#edf2f7')),
        ('TEXTCOLOR', (0, 0), (-1, -1), colors.black),
        ('ALIGN', (0, 0), (-1, -1), 'LEFT'),
        ('FONTNAME', (0, 0), (0, -1), 'Helvetica-Bold'),
        ('FONTSIZE', (0, 0), (-1, -1), 10),
        ('BOTTOMPADDING', (0, 0), (-1, -1), 10),
        ('TOPPADDING', (0, 0), (-1, -1), 10),
        ('GRID', (0, 0), (-1, -1), 1, colors.HexColor('#cbd5e0')),
    ]))
    
    story.append(info_table)
    story.append(PageBreak())
    
    # Main content - simple parsing
    lines = md_content.split('\n')
    current_para = []
    
    for line in lines:
        line = line.rstrip()
        
        if line.startswith('# '):
            if current_para:
                story.append(Paragraph(' '.join(current_para), body_style))
                current_para = []
            story.append(Spacer(1, 0.2*inch))
            story.append(Paragraph(line[2:], title_style))
            story.append(Spacer(1, 0.15*inch))
            
        elif line.startswith('## '):
            if current_para:
                story.append(Paragraph(' '.join(current_para), body_style))
                current_para = []
            story.append(Spacer(1, 0.1*inch))
            story.append(Paragraph(line[3:], heading2_style))
            story.append(Spacer(1, 0.08*inch))
            
        elif line.startswith('### '):
            if current_para:
                story.append(Paragraph(' '.join(current_para), body_style))
                current_para = []
            story.append(Paragraph(line[4:], heading3_style))
            story.append(Spacer(1, 0.05*inch))
            
        elif line == '' or line.startswith('```'):
            if current_para:
                story.append(Paragraph(' '.join(current_para), body_style))
                current_para = []
            if line == '':
                story.append(Spacer(1, 0.05*inch))
            
        elif line.startswith('---'):
            if current_para:
                story.append(Paragraph(' '.join(current_para), body_style))
                current_para = []
            story.append(PageBreak())
            
        elif line.startswith('|'):
            pass  # Skip tables
            
        elif line.startswith('- ') or line.startswith('* '):
            if current_para:
                story.append(Paragraph(' '.join(current_para), body_style))
                current_para = []
            story.append(Paragraph("• " + line[2:], body_style))
            
        else:
            if line.strip() and not line.startswith('['):
                current_para.append(line.strip())
    
    # Tambahkan paragraf terakhir
    if current_para:
        story.append(Paragraph(' '.join(current_para), body_style))
    
    # Build PDF
    print("🔄 Generating PDF...")
    doc.build(story)
    
    print(f"✅ PDF berhasil dibuat: {pdf_output}")
    file_size = os.path.getsize(pdf_output) / 1024
    print(f"📦 Ukuran file: {file_size:.2f} KB")


def markdown_to_html(markdown_file):
    """Konversi Markdown ke HTML"""
    
    with open(markdown_file, 'r', encoding='utf-8') as f:
        md_content = f.read()
    
    html_content = markdown(md_content, extensions=['extra', 'codehilite', 'toc'])
    
    # Tambahkan styling
    html_template = f"""
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Dokumentasi Arsitektur Teknologi - E-Learning Bimbel Gambar Villa Merah</title>
        <style>
            @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
            
            * {{
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }}
            
            body {{
                font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
                line-height: 1.6;
                color: #333;
                background: white;
                padding: 20px;
                max-width: 1200px;
                margin: 0 auto;
            }}
            
            /* Headings */
            h1 {{
                font-size: 2.5em;
                margin: 0.5em 0;
                color: #1a202c;
                border-bottom: 3px solid #3182ce;
                padding-bottom: 0.3em;
                page-break-after: avoid;
            }}
            
            h2 {{
                font-size: 2em;
                margin: 1em 0 0.5em 0;
                color: #2d3748;
                border-left: 4px solid #3182ce;
                padding-left: 1em;
                page-break-after: avoid;
            }}
            
            h3 {{
                font-size: 1.5em;
                margin: 0.8em 0 0.4em 0;
                color: #4a5568;
                page-break-after: avoid;
            }}
            
            h4, h5, h6 {{
                margin: 0.6em 0 0.3em 0;
                page-break-after: avoid;
            }}
            
            /* Paragraf */
            p {{
                margin: 0.8em 0;
                text-align: justify;
            }}
            
            /* Links */
            a {{
                color: #3182ce;
                text-decoration: none;
                border-bottom: 1px dotted #3182ce;
            }}
            
            a:hover {{
                border-bottom: 1px solid #3182ce;
            }}
            
            /* Lists */
            ul, ol {{
                margin: 1em 0;
                padding-left: 2em;
            }}
            
            li {{
                margin: 0.4em 0;
            }}
            
            /* Tables */
            table {{
                width: 100%;
                border-collapse: collapse;
                margin: 1.5em 0;
                page-break-inside: avoid;
            }}
            
            th, td {{
                border: 1px solid #cbd5e0;
                padding: 0.8em;
                text-align: left;
            }}
            
            th {{
                background-color: #edf2f7;
                font-weight: 600;
                color: #1a202c;
            }}
            
            tr:nth-child(even) {{
                background-color: #f7fafc;
            }}
            
            /* Code blocks */
            pre {{
                background-color: #1a202c;
                color: #e2e8f0;
                padding: 1.5em;
                border-radius: 0.5em;
                overflow-x: auto;
                margin: 1em 0;
                font-family: 'Courier New', monospace;
                font-size: 0.9em;
                line-height: 1.4;
                page-break-inside: avoid;
            }}
            
            code {{
                font-family: 'Courier New', monospace;
                background-color: #f7fafc;
                padding: 0.2em 0.4em;
                border-radius: 0.3em;
                color: #d63384;
                font-size: 0.95em;
            }}
            
            pre code {{
                background-color: transparent;
                color: #e2e8f0;
                padding: 0;
            }}
            
            /* Blockquotes */
            blockquote {{
                border-left: 4px solid #3182ce;
                padding-left: 1em;
                margin: 1em 0;
                color: #4a5568;
                font-style: italic;
                background-color: #f7fafc;
                padding: 1em;
            }}
            
            /* Images & Diagrams */
            img {{
                max-width: 100%;
                height: auto;
                margin: 1em 0;
                page-break-inside: avoid;
            }}
            
            /* Horizontal rule */
            hr {{
                border: none;
                border-top: 2px solid #cbd5e0;
                margin: 2em 0;
                page-break-after: avoid;
            }}
            
            /* Special formatting */
            .diagram {{
                background-color: #f7fafc;
                border: 1px solid #cbd5e0;
                padding: 1em;
                border-radius: 0.5em;
                font-family: monospace;
                overflow-x: auto;
                page-break-inside: avoid;
            }}
            
            /* Cover page styling */
            .cover {{
                text-align: center;
                padding: 3em 0;
                page-break-after: always;
                border-bottom: 3px solid #3182ce;
            }}
            
            .cover h1 {{
                border: none;
                margin: 0.5em 0;
                font-size: 3em;
            }}
            
            .cover-subtitle {{
                font-size: 1.5em;
                color: #4a5568;
                margin: 1em 0;
            }}
            
            .cover-meta {{
                font-size: 0.95em;
                color: #718096;
                margin-top: 2em;
            }}
            
            /* Table of contents */
            .toc {{
                page-break-after: always;
                padding: 2em;
                background-color: #f7fafc;
                border-radius: 0.5em;
                margin: 2em 0;
            }}
            
            .toc h2 {{
                border: none;
                padding: 0;
                margin: 0 0 1em 0;
            }}
            
            .toc ul {{
                list-style: none;
                padding: 0;
            }}
            
            .toc li {{
                margin: 0.5em 0;
            }}
            
            .toc a {{
                color: #3182ce;
            }}
            
            /* Footer */
            .footer {{
                margin-top: 3em;
                padding-top: 1em;
                border-top: 1px solid #cbd5e0;
                font-size: 0.85em;
                color: #718096;
                text-align: center;
                page-break-before: always;
            }}
            
            /* Print specific */
            @media print {{
                body {{
                    padding: 0;
                }}
                
                a {{
                    color: #2c3e50;
                    border: none;
                }}
                
                h1, h2, h3 {{
                    page-break-after: avoid;
                }}
                
                table, img {{
                    page-break-inside: avoid;
                }}
            }}
        </style>
    </head>
    <body>
        <div class="cover">
            <h1>📚 Dokumentasi Arsitektur Teknologi</h1>
            <div class="cover-subtitle">E-Learning Bimbel Gambar Villa Merah</div>
            <div class="cover-meta">
                <p><strong>Versi:</strong> 1.0</p>
                <p><strong>Tanggal:</strong> 25 Juli 2026</p>
                <p><strong>Status:</strong> Production Ready</p>
            </div>
        </div>
        
        <div class="toc">
            <h2>📋 Daftar Isi</h2>
            <ul>
                <li><a href="#ringkasan-eksekutif">Ringkasan Eksekutif</a></li>
                <li><a href="#arsitektur-sistem">Arsitektur Sistem</a></li>
                <li><a href="#stack-teknologi">Stack Teknologi</a></li>
                <li><a href="#struktur-aplikasi">Struktur Aplikasi</a></li>
                <li><a href="#database-schema">Database Schema</a></li>
                <li><a href="#module-dan-features">Module dan Features</a></li>
                <li><a href="#infrastruktur-dan-deployment">Infrastruktur dan Deployment</a></li>
                <li><a href="#keamanan">Keamanan</a></li>
                <li><a href="#performance-dan-scalability">Performance dan Scalability</a></li>
            </ul>
        </div>
        
        {html_content}
        
        <div class="footer">
            <p>© 2026 E-Learning Bimbel Gambar Villa Merah. All Rights Reserved.</p>
            <p>Dokumentasi ini adalah properti konfidensial dan hanya untuk penggunaan internal.</p>
        </div>
    </body>
    </html>
    """
    
    return html_template


def main():
    """Main function"""
    
    markdown_file = r"e:\lms\laragon\www\elearning-02\docs\ARSITEKTUR-TEKNOLOGI.md"
    pdf_output = r"e:\lms\laragon\www\elearning-02\docs\ARSITEKTUR-TEKNOLOGI.pdf"
    
    if not os.path.exists(markdown_file):
        print(f"❌ File tidak ditemukan: {markdown_file}")
        sys.exit(1)
    
    print("=" * 60)
    print("🚀 Markdown to PDF Converter (ReportLab)")
    print("=" * 60)
    
    try:
        create_pdf_from_markdown(markdown_file, pdf_output)
        print("=" * 60)
        print("✨ Selesai!")
        print("=" * 60)
    except Exception as e:
        print(f"❌ Error: {str(e)}")
        import traceback
        traceback.print_exc()
        sys.exit(1)


if __name__ == "__main__":
    main()
