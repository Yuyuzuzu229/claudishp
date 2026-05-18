from PIL import Image
import sys

path = sys.argv[1]
img = Image.open(path).convert("L")
w, h = img.size
print(f"Dimensions: {w}x{h}")

# Edge detection - check horizontal and vertical gradients
# Sample every 50 pixels
edge_h = 0
edge_v = 0
sample_count = 0
for x in range(50, w-50, 30):
    for y in range(50, h-50, 30):
        dx = abs(img.getpixel((x+5, y)) - img.getpixel((x-5, y)))
        dy = abs(img.getpixel((x, y+5)) - img.getpixel((x, y-5)))
        if dx > 30: edge_h += 1
        if dy > 30: edge_v += 1
        sample_count += 1

print(f"Echantillons: {sample_count}")
print(f"Bords horizontaux: {edge_h} ({edge_h*100//sample_count}%)")
print(f"Bords verticaux: {edge_v} ({edge_v*100//sample_count}%)")
print(f"Total: {(edge_h+edge_v)*100//(sample_count*2)}%")

# Find content regions by scanning rows for non-white pixels
content_rows = []
for y in range(0, h, 5):
    row_dark = sum(1 for x in range(0, w, 10) if img.getpixel((x, y)) < 200)
    if row_dark > w // 50:
        content_rows.append(y)

if content_rows:
    # Find contiguous regions
    regions = []
    start = content_rows[0]
    prev = content_rows[0]
    for r in content_rows[1:]:
        if r - prev > 20:
            regions.append((start, prev))
            start = r
        prev = r
    regions.append((start, prev))
    
    print(f"\nRegions de contenu ({len(regions)}):")
    for i, (s, e) in enumerate(regions):
        pct_s = s*100//h
        pct_e = e*100//h
        print(f"  Region {i+1}: lignes {s}-{e} ({pct_s}%-{pct_e}% de la page)")
else:
    print("Pas de contenu sombre detecte")

img.close()
