from PIL import Image
import sys, os

path = sys.argv[1]
name = os.path.splitext(os.path.basename(path))[0]
img = Image.open(path).convert("L")
w, h = img.size
print(f"Analyse de: {name} ({w}x{h})")

pixels = img.load()

# Step 1: Find all horizontal line segments (wireframe boxes)
h_segments = []  # (x1, x2, y)
for y in range(0, h, 2):
    x = 0
    while x < w:
        if pixels[x, y] < 180:
            x1 = x
            while x < w and pixels[x, y] < 180:
                x += 1
            x2 = x
            if x2 - x1 > 8:  # Min width
                h_segments.append((x1, x2, y))
        x += 1

# Step 2: Find vertical line segments
v_segments = []  # (x, y1, y2)
for x in range(0, w, 2):
    y = 0
    while y < h:
        if pixels[x, y] < 180:
            y1 = y
            while y < h and pixels[x, y] < 180:
                y += 1
            y2 = y
            if y2 - y1 > 8:  # Min height
                v_segments.append((x, y1, y2))
        y += 1

# Step 3: Find rectangles (boxes) - look for matching top/bottom/left/right
rects = []
for x1, x2, y_top in h_segments:
    for x1b, x2b, y_bot in h_segments:
        if y_bot > y_top + 20 and abs(x1 - x1b) < 10 and abs(x2 - x2b) < 10:
            # Check if left and right edges exist
            has_left = any(abs(x - x1) < 5 and abs(y1 - y_top) < 20 and abs(y2 - y_bot) < 20 for x, y1, y2 in v_segments)
            has_right = any(abs(x - x2) < 5 and abs(y1 - y_top) < 20 and abs(y2 - y_bot) < 20 for x, y1, y2 in v_segments)
            if has_left and has_right:
                rects.append((x1, y_top, x2, y_bot))

# Merge nearby rects
rects.sort()
merged = []
for r in rects:
    if not merged:
        merged.append(r)
    else:
        last = merged[-1]
        if abs(last[0] - r[0]) < 10 and abs(last[1] - r[1]) < 10 and abs(last[2] - r[2]) < 10 and abs(last[3] - r[3]) < 10:
            continue
        merged.append(r)

# Generate SVG
svg = f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {w} {h}" width="{w//3}" height="{h//3}">\n'
svg += f'  <rect x="0" y="0" width="{w}" height="{h}" fill="#f8f9fa"/>\n'

# Draw rectangles
for x1, y1, x2, y2 in merged:
    bw = x2 - x1
    bh = y2 - y1
    fill = "#e8f0fe" if bh > 50 else "none"
    svg += f'  <rect x="{x1}" y="{y1}" width="{bw}" height="{bh}" fill="{fill}" stroke="#333" stroke-width="2"/>\n'
    svg += f'  <text x="{x1+8}" y="{y1+20}" font-family="monospace" font-size="14" fill="#666">{bw}x{bh}</text>\n'

# Draw significant text-like elements (short horizontal segments grouped)
# Find text lines (short segments, same y level)
from collections import defaultdict
text_lines = defaultdict(list)
for x1, x2, y in h_segments:
    if x2 - x1 < 200:  # Short segments (text)
        y_key = round(y / 20) * 20
        text_lines[y_key].append((x1, x2, y))

text_count = 0
for y_key, segs in sorted(text_lines.items()):
    if len(segs) >= 2 and text_count < 30:
        for x1, x2, y in segs[:5]:
            text_count += 1
            svg += f'  <rect x="{x1}" y="{y}" width="{x2-x1}" height="3" fill="#333" rx="1"/>\n'

svg += '</svg>'

out_path = path.replace('.png', '.svg')
with open(out_path, 'w', encoding='utf-8') as f:
    f.write(svg)

print(f"SVG genere: {out_path}")
print(f"Rectangles trouves: {len(merged)}")
print(f"Segments horizontaux: {len(h_segments)}")
print(f"Segments verticaux: {len(v_segments)}")
print(f"Lignes de texte detectees: {text_count}")
