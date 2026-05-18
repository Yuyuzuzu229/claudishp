from PIL import Image
import sys

path = sys.argv[1]
img = Image.open(path).convert("L")
w, h = img.size
print(f"Analyse de: {path.split(chr(92))[-1]}")
print(f"Dimensions: {w}x{h}\n")

# Horizontal scanlines at every 2% of height
print("=== Structure verticale (tranches horizontales) ===")
for pct in range(0, 101, 2):
    y = h * pct // 100
    dark_pixels = []
    for x in range(0, w, 5):
        if img.getpixel((x, y)) < 180:
            dark_pixels.append(x)
    
    if dark_pixels:
        coverage = len(dark_pixels) * 100 // (w // 5)
        # Find continuous dark regions
        regions = []
        start = dark_pixels[0]
        prev = dark_pixels[0]
        for p in dark_pixels[1:]:
            if p - prev > 20:
                regions.append((start, prev))
                start = p
            prev = p
        regions.append((start, prev))
        
        region_str = []
        for s, e in regions:
            region_str.append(f"{s*100//w}%-{e*100//w}%")
        print(f"  {pct:2d}% [{'#'*coverage}{' '*(30-coverage)}] ({coverage:2d}%) {', '.join(region_str[:3])}")

img.close()
