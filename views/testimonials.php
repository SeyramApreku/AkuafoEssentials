<?php
// Partners / Testimonials: every partner's photo and feedback as a wall of dark cards (four card styles,
// repeating), each inviting visitors to browse that restaurant's exclusive recipes through membership.
require_once __DIR__ . '/../core/core.php';
$pageTitle = 'Our Partners | Akuafo & Essentials';
$stories = testimonials();
$recipeCounts = partner_recipe_counts();
$isMember = (bool)(customer_id() && member_tier());
include __DIR__ . '/layout/header.php';
?>
<style>
  .tw-page { background: #D9D7D2; }
  .tw-wall { columns: 1; column-gap: 28px; }
  @media (min-width: 768px)  { .tw-wall { columns: 2; } }
  @media (min-width: 1100px) { .tw-wall { columns: 3; } }
  .tw-item { break-inside: avoid; margin-bottom: 36px; }
  .tw-card { background: #262522; color: #EDEAE3; border-radius: 22px; position: relative; }
  .tw-muted { color: #A9A49A; }
  .tw-name { color: #F4EFE4; }
  .tw-hl { color: #E9D86B; }                       /* soft yellow highlight, as in the reference */
  .tw-photo { object-fit: cover; background: #3A3934; }
  .tw-ring { box-shadow: 0 0 0 4px #E9D86B; }
  .tw-bubble::after { content: ''; position: absolute; left: 44px; bottom: -14px; border: 14px solid transparent; border-top-color: #262522; border-bottom: 0; border-left: 0; }
  .tw-cta { border-top: 1px solid rgba(237,234,227,.12); }
  .tw-btn { display: inline-flex; align-items: center; gap: 8px; padding: 9px 16px; border-radius: 999px; font-size: 12px; font-weight: 600; background: #E9D86B; color: #1A1916; transition: transform .15s ease, background .15s ease; }
  .tw-btn:hover, .tw-btn:focus-visible { background: #F4E57E; transform: translateY(-1px); outline: none; }
  .tw-quote { font-family: Georgia, serif; line-height: .6; }
</style>

<main class="tw-page">
  <div class="max-w-7xl mx-auto px-6 py-14 md:py-20">
    <div class="grid md:grid-cols-2 gap-8 items-end mb-14">
      <div>
        <p class="text-xs uppercase tracking-[0.3em] mb-4" style="color:var(--accent)">Testimonials</p>
        <h1 class="font-display font-light text-5xl md:text-7xl leading-[0.95]">What our partner<br>kitchens say</h1>
      </div>
      <div class="md:justify-self-end md:text-right max-w-sm">
        <p class="text-sm leading-relaxed mb-5" style="color:#4F4B43">The chefs, restaurants and hotels across Ghana who cook with produce traced back to the farm. Their exclusive recipes are part of every Akuafo membership.</p>
        <a href="mailto:akuafoessentials@gmail.com?subject=<?= rawurlencode('Partnering with Akuafo & Essentials') ?>" class="inline-flex items-center gap-2 px-6 py-3 text-sm font-medium" style="background:#1A1916;color:#F4EFE4">Partner with us →</a>
      </div>
    </div>

    <?php if (!$stories): ?>
      <p class="text-sm py-16 text-center" style="color:#4F4B43">Partner stories are coming soon.</p>
    <?php else: ?>
    <div class="tw-wall">
      <?php foreach ($stories as $i => $t):
        $business = $t['business'];
        $n = (int)($recipeCounts[$business] ?? 0);
        $who = implode(' · ', array_filter([$t['role'], $business !== $t['name'] ? $business : '']));
        $photo = img($t['photo'], 400, 400);
        $style = $i % 4;
        // The membership invitation shown on every card.
        ob_start(); ?>
          <div class="tw-cta mt-6 pt-5">
            <p class="text-xs leading-relaxed mb-3 tw-muted">
              <?php if ($isMember): ?>
                Browse <?= $n ? $n . ' ' : '' ?>exclusive recipe<?= $n === 1 ? '' : 's' ?> from <span class="tw-hl"><?= e($business) ?></span>, included in your membership.
              <?php else: ?>
                Browse recipes from <span class="tw-hl"><?= e($business) ?></span> by becoming a member of the Akuafo community.
              <?php endif; ?>
            </p>
            <a class="tw-btn" href="<?= $isMember ? url('views/member_recipes.php') : url('views/membership.php') ?>"><?= $isMember ? 'See their recipes' : 'Explore membership' ?> <span aria-hidden="true">→</span></a>
          </div>
        <?php $cta = ob_get_clean(); ?>

        <article class="tw-item">
        <?php if ($style === 0): ?>
          <!-- Photo in a circle, overlapping the top corner -->
          <div class="tw-card p-7 pt-12 mt-10">
            <img src="<?= e($photo) ?>" alt="<?= e($t['name']) ?>" class="tw-photo tw-ring w-20 h-20 rounded-full absolute -top-10 right-7" loading="lazy">
            <span class="tw-quote tw-hl text-6xl block mb-2" aria-hidden="true">“</span>
            <blockquote class="text-base leading-relaxed mb-6"><?= e($t['quote']) ?></blockquote>
            <div class="border-t pt-4" style="border-color:rgba(237,234,227,.12)"><p class="text-sm font-semibold tw-name"><?= e($t['name']) ?></p><p class="text-xs tw-hl mt-0.5"><?= e($who) ?></p></div>
            <?= $cta ?>
          </div>
        <?php elseif ($style === 1): ?>
          <!-- Speech bubble, with the person underneath -->
          <div class="tw-card tw-bubble p-7">
            <blockquote class="text-base leading-relaxed">“<?= e($t['quote']) ?>”</blockquote>
            <?= $cta ?>
          </div>
          <div class="flex items-center gap-3 mt-6 ml-4">
            <img src="<?= e($photo) ?>" alt="<?= e($t['name']) ?>" class="tw-photo w-12 h-12 rounded-full" loading="lazy">
            <div><p class="text-sm font-semibold" style="color:#1A1916"><?= e($t['name']) ?></p><p class="text-xs" style="color:#4F4B43"><?= e($who) ?></p></div>
          </div>
        <?php elseif ($style === 2): ?>
          <!-- Large photo on top, quote below -->
          <div class="tw-card overflow-hidden">
            <img src="<?= e($photo) ?>" alt="<?= e($t['name']) ?>" class="tw-photo w-full aspect-[4/3]" loading="lazy">
            <div class="p-7">
              <p class="font-display text-2xl tw-name mb-1"><?= e($t['name']) ?></p>
              <p class="text-xs tw-hl mb-4"><?= e($who) ?></p>
              <blockquote class="text-sm leading-relaxed tw-muted">“<?= e($t['quote']) ?>”</blockquote>
              <?= $cta ?>
            </div>
          </div>
        <?php else: ?>
          <!-- Big quote marks, centred text, person in the footer -->
          <div class="tw-card p-8 text-center">
            <span class="tw-quote tw-hl text-7xl block mb-4" aria-hidden="true">“</span>
            <blockquote class="font-display text-xl leading-snug tw-name mb-6"><?= e($t['quote']) ?></blockquote>
            <div class="flex items-center justify-center gap-3 border-t pt-5" style="border-color:rgba(237,234,227,.12)">
              <img src="<?= e($photo) ?>" alt="<?= e($t['name']) ?>" class="tw-photo w-11 h-11 rounded-full" loading="lazy">
              <div class="text-left"><p class="text-sm font-semibold tw-name"><?= e($t['name']) ?></p><p class="text-xs tw-hl"><?= e($who) ?></p></div>
            </div>
            <div class="text-left"><?= $cta ?></div>
          </div>
        <?php endif; ?>
        </article>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</main>
<?php include __DIR__ . '/layout/footer.php'; ?>
