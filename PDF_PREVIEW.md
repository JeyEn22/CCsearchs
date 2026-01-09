PDF Preview Integration

This project now uses an external PDF→image REST API to generate thumbnails/previews (first page only) instead of server-side ImageMagick/Ghostscript/etc. This keeps the app compatible with free/shared hosting.

Configuration

- Set environment variable PDF_API_KEY to your API key for the conversion service (e.g., PDF.co). Example (Windows):
  setx PDF_API_KEY "your_api_key_here"

- Optionally set PDF_API_BASE to a different API base URL (default: https://api.pdf.co/v1).

Behavior

- Upload flow: the server uploads your PDF to the external API, requests conversion of only the first page to JPG, downloads the resulting image, resizes to a thumbnail (200px width) if GD is available, and stores the result under uploads/previews/.
- If the API or download fails for any reason, the app creates a local placeholder image (keeps UX intact).

Security & Cost

- Keep your API key private; store it in environment variables rather than committing to the repo.
- Be aware of API usage limits and costs (many services have free tiers with limits). Monitor usage in your provider dashboard.

Notes

- No server-side ImageMagick, Ghostscript, or exec() calls are used for PDF conversion.
- If you still rely on a local ImageMagick installation or Ghostscript, informational test scripts remain (e.g., test_imagick.php, test_publication_setup.php) for diagnostics but are not required for production previews.

Troubleshooting

- If previews are missing, confirm PDF_API_KEY is set and that the web server can make outbound HTTPS requests (cURL enabled).
- Check server error logs for entries starting with "PDF preview:" to see details of failures.
