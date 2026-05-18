from PIL import Image
import sys

path = sys.argv[1]
img = Image.open(path).convert("L")
w, h = img.size
print(f"Dimensions: {w}x{h}")

# Scan specific rows in detail to find wireframe boxes
# The content regions are at ~26%, ~60%, ~83%, ~88% of height
scan_rows = [h*26//100, h*60//100, h*83//100, h*88//100]
# Also check for nav at top
scan_rows.insert(0, 5)

for y in scan_rows:
    print(f"\n--- Scan ligne {y} ({y*100//h}%) ---")
    # Find dark segments (wireframe elements)
    segments = []
    in_dark = False
    seg_start = 0
    for x in range(0, w, 3):
        pixel = img.getpixel((x, y))
        is_dark = pixel < 180
        if is_dark and not in_dark:
            seg_start = x
            in_dark = True
        elif not is_dark and in_dark:
            segments.append((seg_start, x, x-seg_start))
            in_dark = False
    if in_dark:
        segments.append((seg_start, w, w-seg_start))
    
    if not segments:
        print("  Rien (ligne blanche)")
    else:
        print(f"  {len(segments)} segments sombres:")
        for s, e, length in segments:
            pct_s = s*100//w
            pct_e = e*100//w
            print(f"    {pct_s}%-{pct_e}% (largeur={length}px)")

# Now do a vertical scan to find horizontal dividers
print("\n\n--- Scan vertical au centre ---")
segments_v = []
in_dark = False
seg_start = 0
for y in range(0, h, 3):
    pixel = img.getpixel((w//2, y))
    is_dark = pixel < 180
    if is_dark and not in_dark:
        seg_start = y
        in_dark = True
    elif not is_dark and in_dark:
        segments_v.append((seg_start, y, y-seg_start))
        in_dark = False
if in_dark:
    segments_v.append((seg_start, h, h-seg_start))

if segments_v:
    print(f"  {len(segments_v)} segments sombres verticaux:")
    for s, e, length in segments_v:
        pct_s = s*100//h
        pct_e = e*100//h
        print(f"    {pct_s}%-{pct_e}% (hauteur={length}px)")

img.close()
