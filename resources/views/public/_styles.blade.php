<style>
.bs-page { padding: 170px 0 60px; }
.bs-card { border: 1px solid #e5e5e5; border-radius: 8px; padding: 16px; margin-bottom: 16px; background: #fff; }
.bs-price { font-size: 1.3rem; font-weight: 700; }
.bs-muted { color: #777; }
.bs-thumb { width: 100%; max-width: 260px; height: 170px; object-fit: cover; border-radius: 6px; background: #eee; }
.bs-gallery img { height: 160px; object-fit: cover; border-radius: 6px; width: 100%; }
.bs-gallery a, .bs-room-photos a { display: block; cursor: zoom-in; }
.bs-room-cover { width: 100%; height: 200px; object-fit: cover; border-radius: 6px; background: #eee; display: block; }
.bs-room-photos { position: relative; }
.bs-room-photos .bs-room-thumbs { display: flex; gap: 6px; margin-top: 6px; }
.bs-room-photos .bs-room-thumbs img { width: 25%; height: 52px; object-fit: cover; border-radius: 4px; }
.bs-room-photos .bs-more { position: absolute; right: 8px; top: 8px; background: rgba(0,0,0,.65); color: #fff; border-radius: 4px; padding: 2px 8px; font-size: .8rem; }
.bs-lightbox { position: fixed; inset: 0; z-index: 10000; background: rgba(0,0,0,.92); display: flex; align-items: center; justify-content: center; }
.bs-lightbox[hidden] { display: none; }
.bs-lightbox img { max-width: 88%; max-height: 84%; border-radius: 6px; }
.bs-lightbox button { position: absolute; background: none; border: 0; color: #fff; font-size: 40px; line-height: 1; padding: 8px 14px; }
.bs-lightbox .bs-lb-close { top: 10px; right: 10px; }
.bs-lightbox .bs-lb-prev { left: 4px; top: 50%; transform: translateY(-50%); }
.bs-lightbox .bs-lb-next { right: 4px; top: 50%; transform: translateY(-50%); }
.bs-lightbox .bs-lb-caption { position: absolute; bottom: 14px; left: 0; right: 0; text-align: center; color: #ddd; font-size: .9rem; }
.bs-featured { display: block; height: 100%; border: 1px solid #e5e5e5; border-radius: 8px; overflow: hidden; background: #fff; color: inherit; transition: box-shadow .2s; }
.bs-featured:hover { text-decoration: none; color: inherit; box-shadow: 0 6px 18px rgba(0,0,0,.12); }
.bs-featured-photos { display: grid; gap: 3px; height: 200px; grid-template-columns: 2fr 1fr; grid-template-rows: 1fr 1fr; }
.bs-featured-photos img { width: 100%; height: 100%; object-fit: cover; background: #eee; min-height: 0; }
.bs-featured-photos img:first-child { grid-row: 1 / 3; }
.bs-featured-photos-1 { grid-template-columns: 1fr; }
.bs-featured-photos-2 img:nth-child(2) { grid-row: 1 / 3; }
.bs-featured-body { padding: 12px 14px; }
.bs-room-amenities summary { cursor: pointer; color: var(--site-primary, #1675ac); }
.bs-room-amenities ul { padding-left: 18px; }
.bs-map { width: 100%; height: 300px; border: 0; border-radius: 6px; display: block; margin-bottom: 8px; }
.bs-stay-form label { font-size: 0.85rem; margin-bottom: 2px; }
/* Bootstrap 5 utilities used on these pages (the public theme ships Bootstrap 4) */
.gap-2 { gap: .5rem; }
.gap-3 { gap: 1rem; }
.text-end { text-align: right !important; }
.me-2 { margin-right: .5rem !important; }
.row.g-2 { margin-left: -4px; margin-right: -4px; }
.row.g-2 > * { padding-left: 4px; padding-right: 4px; margin-bottom: 8px; }
</style>
