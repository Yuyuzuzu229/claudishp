from PIL import Image
import sys

path = sys.argv[1]
img = Image.open(path)
w, h = img.size
print(f"Dimensions: {w}x{h}")

small = img.resize((200, int(200*h/w)))
px = small.load()
sw, sh = small.size

print("\n=== Bandes horizontales ===")
for row in range(5):
    y_start = row * sh // 5
    y_end = (row+1) * sh // 5
    colors = {}
    for x in range(0, sw, 2):
        c = px[x, (y_start+y_end)//2]
        r, g, b = c[0]//32*32, c[1]//32*32, c[2]//32*32
        key = f"RGB({r},{g},{b})"
        colors[key] = colors.get(key, 0) + 1
    top_color = max(colors, key=colors.get)
    print(f"  Bande {row+1}: {top_color}")

print("\n=== Couleurs par zone ===")
top_vals = [px[x, 3] for x in range(0, sw, 3)]
avg_top = tuple(sum(c)//len(top_vals) for c in zip(*top_vals))
print(f"  Navbar (haut): RGB{avg_top}")

mid_vals = [px[x, sh//3] for x in range(0, sw, 3)]
avg_mid = tuple(sum(c)//len(mid_vals) for c in zip(*mid_vals))
print(f"  Hero (1/3): RGB{avg_mid}")

low_vals = [px[x, sh*2//3] for x in range(0, sw, 3)]
avg_low = tuple(sum(c)//len(low_vals) for c in zip(*low_vals))
print(f"  Milieu (2/3): RGB{avg_low}")

bot_vals = [px[x, sh-5] for x in range(0, sw, 3)]
avg_bot = tuple(sum(c)//len(bot_vals) for c in zip(*bot_vals))
print(f"  Footer (bas): RGB{avg_bot}")

print("\n=== Composition par bande (% pixels clairs/fonces/bleus) ===")
for band_idx in range(5):
    y = band_idx * sh // 5 + sh // 10
    white_count = sum(1 for x in range(0, sw, 2) if px[x,y][0] > 200 and px[x,y][1] > 200 and px[x,y][2] > 200)
    dark_count = sum(1 for x in range(0, sw, 2) if px[x,y][0] < 60 and px[x,y][1] < 60 and px[x,y][2] < 60)
    blue_count = sum(1 for x in range(0, sw, 2) if px[x,y][2] > px[x,y][0] + 25 and px[x,y][2] > px[x,y][1] + 25)
    total = sw // 2
    print(f"  Bande {band_idx+1}: blanc={white_count*100//total}% bleu={blue_count*100//total}% fonce={dark_count*100//total}%")

img.close()
