/* eslint-disable no-restricted-globals */

// Minimal PWA service worker for caching static assets.
// Note: this app uses dynamic PHP pages, so we avoid caching personalized HTML.
const CACHE_NAME = "cineflix-static-v1";

// Make URLs relative to where this service worker is located.
// This app lives under /CINEFLIX2, so we can't assume server-root paths (e.g. "/manifest.json").
const BASE_PATH = self.location.pathname.replace(/\/service-worker\.js$/, "");

const STATIC_ASSET_PATHS = [
  "manifest.json",
  "common.css",
  "homepage.css",
  "script.js",
  "chatbot.css",
  "chatbot.js",
  "icon/google-icon.png",
  "logo/newlogo1.png"
];

const STATIC_ASSETS = STATIC_ASSET_PATHS.map((p) => `${BASE_PATH}/${p}`);

self.addEventListener("install", (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      // Cache what we can; ignore missing assets.
      return Promise.all(
        STATIC_ASSETS.map((url) =>
          cache.add(url).catch(() => {
            /* ignore missing */
          })
        )
      );
    })
  );
  self.skipWaiting();
});

self.addEventListener("activate", (event) => {
  event.waitUntil(self.clients.claim());
});

self.addEventListener("fetch", (event) => {
  if (event.request.method !== "GET") return;

  const url = new URL(event.request.url);
  if (url.origin !== location.origin) return;

  event.respondWith(
    caches.match(event.request).then((cached) => {
      return (
        cached ||
        fetch(event.request).catch(() => {
          // If offline, fall back to cached content (if any).
          return cached || new Response("Offline", { status: 503 });
        })
      );
    })
  );
});

