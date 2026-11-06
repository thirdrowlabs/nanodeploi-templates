"""A Python process listening on a port, standard library only.

No pip install is needed, so this checks the Python runtime itself:
the container starts, listens on PORT and Traefik routes traffic to it.
"""

import html
import json
import os
import platform
from datetime import datetime, timezone
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer

PORT = int(os.environ.get("PORT", "8000"))
STARTED_AT = datetime.now(timezone.utc).isoformat(timespec="seconds")


class Handler(BaseHTTPRequestHandler):
    def do_GET(self):
        if self.path == "/health":
            body = json.dumps({"app": "09-python-web", "ok": True, "python": platform.python_version()}) + "\n"
            self.respond(200, "application/json", body)
            return

        body = f"""<!doctype html>
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Demo 09 · Python web</title>
<body style="font-family: system-ui, sans-serif; max-width: 40rem; margin: 4rem auto; padding: 0 1rem">
<h1>Python is running</h1>
<p>Python {platform.python_version()}, listening on port {PORT}, started {STARTED_AT}.</p>
<p>You asked for <code>{html.escape(self.path)}</code>.</p>
</body>"""
        self.respond(200, "text/html; charset=utf-8", body)

    def respond(self, status, content_type, body):
        data = body.encode()
        self.send_response(status)
        self.send_header("Content-Type", content_type)
        self.send_header("Content-Length", str(len(data)))
        self.end_headers()
        self.wfile.write(data)


if __name__ == "__main__":
    print(f"Listening on {PORT}", flush=True)
    ThreadingHTTPServer(("0.0.0.0", PORT), Handler).serve_forever()
