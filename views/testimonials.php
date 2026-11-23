<?php
// Partners / Testimonials: one quote at a time, switched with the portrait strip or the arrows.
require_once __DIR__ . '/../core/core.php';
$pageTitle = 'Our Partners | Akuafo & Essentials';
$stories = testimonials();
include __DIR__ . '/layout/header.php';
$first = $stories[0] ?? null;
?>
<style>
  .tm-page { background-color: #EFEEEA; position: relative; }
  /* thin vertical grid lines */
  .tm-grid { position: absolute; inset: 0; pointer-events: none;
             background-image: linear-gradient(to right, rgba(26,25,22,.07) 1px, transparent 1px);
             background-size: calc(100% / 6) 100%; }
  @media (max-width: 767px) { .tm-grid { background-size: calc(100% / 3) 100%; } }
  .tm-fade { transition: opacity .28s ease, transform .28s ease; }
  .tm-fade.is-out { opacity: 0; transform: translateY(8px); }
  .tm-thumb { position: relative; flex: 0 0 auto; width: 84px; height: 84px; overflow: hidden; scroll-snap-align: center;
              transition: width .3s ease, height .3s ease, box-shadow .3s ease; background: #D5CBBA; }
  .tm-thumb img { width: 100%; height: 100%; object-fit: cover; filter: grayscale(1) contrast(1.05); transition: filter .3s ease, transform .3s ease; }
  .tm-thumb::after { content: ''; position: absolute; inset: 0; background: var(--accent); mix-blend-mode: multiply; opacity: 0; transition: opacity .3s ease; }
  .tm-thumb:hover img { transform: scale(1.04); }
  .tm-thumb.is-active { width: 120px; height: 120px; box-shadow: 0 0 0 2px var(--accent); }
  .tm-thumb.is-active::after { opacity: .55; }
  .tm-thumb:focus-visible { outline: 2px solid var(--foreground); outline-offset: 3px; }
  .tm-strip { scroll-snap-type: x mandatory; scrollbar-width: thin; }
  .tm-arrow { width: 48px; height: 48px; border: 1px solid #1A1916; display: inline-flex; align-items: center; justify-content: center; transition: background .2s, color .2s; }
  .tm-arrow:hover, .tm-arrow:focus-visible { background: #1A1916; color: #EFEEEA; outline: none; }
  @media (prefers-reduced-motion: reduce) { .tm-fade, .tm-thumb, .tm-thumb img, .tm-thumb::after { transition: none; } }
</style>

<main class="tm-page">
  <div class="tm-grid" aria-hidden="true"></div>
  <div class="relative max-w-7xl mx-auto px-6 py-16 md:py-24">

    <!-- Top: label + heading (left), intro + button (right) -->
    <div class="grid md:grid-cols-2 gap-10 items-end mb-16 md:mb-24">
      <div>
        <p class="text-xs uppercase tracking-[0.3em] mb-4" style="color:var(--muted-foreground)">Testimonials</p>
        <h1 class="font-display font-light leading-[0.9] text-6xl md:text-8xl">
          <span class="block" style="color:#1A1916">Our</span>
          <span class="block" style="color:var(--accent)">Partners</span>
        </h1>
      </div>
      <div class="md:justify-self-end md:text-right max-w-sm">
        <p class="text-sm leading-relaxed mb-6" style="color:var(--muted-foreground)">The chefs and kitchens across Ghana who cook with produce traced back to the farm.</p>
        <a href="mailto:akuafoessentials@gmail.com?subject=<?= rawurlencode('Partnering with Akuafo & Essentials') ?>"
           class="inline-flex items-center gap-3 px-6 py-3.5 text-sm font-medium" style="background:#1A1916;color:#F4EFE4">
          Partner With Us
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </a>
      </div>
    </div>

    <?php if (!$first): ?>
      <p class="text-sm py-16 text-center" style="color:var(--muted-foreground)">Partner stories are coming soon.</p>
    <?php else: ?>
    <!-- Centre: quote -->
    <section class="max-w-5xl mx-auto mb-16" aria-roledescription="carousel" aria-label="Partner testimonials">
      <svg width="64" height="52" viewBox="0 0 64 52" class="mb-8" aria-hidden="true" style="color:var(--accent)">
        <path fill="currentColor" d="M0 52V30.6C0 13.4 8.6 3.2 25.8 0l3 6.8C19.4 9.6 14.6 15 14.2 23H26v29H0zm38 0V30.6C38 13.4 46.6 3.2 63.8 0l3 6.8C57.4 9.6 52.6 15 52.2 23H64v29H38z"/>
      </svg>
      <div aria-live="polite">
        <blockquote id="tm-quote" class="tm-fade font-semibold uppercase leading-tight tracking-tight text-2xl md:text-4xl mb-10" style="color:#1A1916">
          <?= e($first['quote']) ?>
        </blockquote>
        <div class="flex flex-wrap items-end justify-between gap-6 border-t pt-6" style="border-color:rgba(26,25,22,.15)">
          <div id="tm-person" class="tm-fade">
            <p id="tm-name" class="text-lg font-medium" style="color:#1A1916"><?= e($first['name']) ?></p>
            <p id="tm-role" class="text-xs uppercase tracking-[0.2em] mt-1" style="color:var(--muted-foreground)"><?= e(implode(' · ', array_filter([$first['role'], $first['business']]))) ?></p>
          </div>
          <div class="flex gap-3">
            <button type="button" class="tm-arrow" id="tm-prev" aria-label="Previous partner">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M19 12H5M11 6l-6 6 6 6"/></svg>
            </button>
            <button type="button" class="tm-arrow" id="tm-next" aria-label="Next partner">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
            </button>
          </div>
        </div>
      </div>
    </section>

    <!-- Bottom: portrait strip -->
    <div class="tm-strip flex items-end gap-4 overflow-x-auto pb-3 md:justify-center" role="tablist" aria-label="Choose a partner">
      <?php foreach ($stories as $k => $t): ?>
        <button type="button" class="tm-thumb <?= $k === 0 ? 'is-active' : '' ?>" data-i="<?= $k ?>" role="tab" aria-selected="<?= $k === 0 ? 'true' : 'false' ?>"
                aria-label="<?= e($t['name'] . ($t['business'] ? ', ' . $t['business'] : '')) ?>">
          <img src="<?= e(img($t['photo'], 240, 240)) ?>" alt="" loading="lazy">
        </button>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</main>

<?php if ($first): ?>
<script>
(function () {
  var items = <?= json_encode(array_map(fn($t) => ['quote' => $t['quote'], 'name' => $t['name'],
                                                    'role' => implode(' · ', array_filter([$t['role'], $t['business']]))], $stories),
                              JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
  var current = 0, busy = false;
  var quote = document.getElementById('tm-quote'), person = document.getElementById('tm-person');
  var thumbs = Array.prototype.slice.call(document.querySelectorAll('.tm-thumb'));
  function show(i) {
    i = (i + items.length) % items.length;
    if (i === current || busy) return;
    busy = true; current = i;
    thumbs.forEach(function (t, k) { t.classList.toggle('is-active', k === i); t.setAttribute('aria-selected', k === i ? 'true' : 'false'); });
    thumbs[i].scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
    quote.classList.add('is-out'); person.classList.add('is-out');
    setTimeout(function () {               // swap the text while it's faded out, then fade back in
      quote.textContent = items[i].quote;
      document.getElementById('tm-name').textContent = items[i].name;
      document.getElementById('tm-role').textContent = items[i].role;
      quote.classList.remove('is-out'); person.classList.remove('is-out');
      busy = false;
    }, 280);
  }
  thumbs.forEach(function (t) { t.addEventListener('click', function () { show(parseInt(t.dataset.i, 10)); }); });
  document.getElementById('tm-prev').addEventListener('click', function () { show(current - 1); });
  document.getElementById('tm-next').addEventListener('click', function () { show(current + 1); });
  document.addEventListener('keydown', function (e) {     // arrow keys work too (not while typing)
    if (/input|textarea|select/i.test((e.target.tagName || ''))) return;
    if (e.key === 'ArrowLeft') show(current - 1);
    if (e.key === 'ArrowRight') show(current + 1);
  });
})();
</script>
<?php endif; ?>
<?php include __DIR__ . '/layout/footer.php'; ?>
