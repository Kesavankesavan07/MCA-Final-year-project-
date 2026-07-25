import os
import pypdf

def check_pdfs():
    paths = [
        "C:/Users/acer/.gemini/antigravity/brain/807e2ec5-4e77-455f-a52f-2ea5d6d0f9d7/media__1782892809828.pdf",
        "C:/Users/acer/.gemini/antigravity/brain/807e2ec5-4e77-455f-a52f-2ea5d6d0f9d7/media__1782892809840.pdf",
        "C:/Users/acer/.gemini/antigravity/brain/807e2ec5-4e77-455f-a52f-2ea5d6d0f9d7/media__1782892810059.pdf"
    ]
    for idx, path in enumerate(paths):
        if os.path.exists(path):
            print(f"\nPDF {idx+1}: {os.path.basename(path)}")
            try:
                reader = pypdf.PdfReader(path)
                print(f"Number of pages: {len(reader.pages)}")
                # Extract cover page text
                first_page = reader.pages[0].extract_text()
                print("Cover Page Text Preview (First 300 chars):")
                print(first_page[:300])
                
                # Check for images on first page
                images = reader.pages[0].images
                print(f"Number of images on page 1: {len(images)}")
                for img_idx, img in enumerate(images):
                    print(f"Image {img_idx+1} name: {img.name}, size: {len(img.data)} bytes")
                    # Save image
                    with open(f"extracted_logo_{idx+1}_{img_idx+1}.png", "wb") as f:
                        f.write(img.data)
                    print(f"Saved image as extracted_logo_{idx+1}_{img_idx+1}.png")
            except Exception as e:
                print(f"Error reading PDF: {e}")
        else:
            print(f"PDF {path} does not exist.")

if __name__ == "__main__":
    check_pdfs()
