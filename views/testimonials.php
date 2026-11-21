<?php
$pageTitle = 'Testimonials | Akuafo & Essentials';
include __DIR__ . '/layout/header.php';
?>
<div class="max-w-7xl mx-auto px-6 py-12">
  <a href="<?= url('index.php') ?>" class="text-xs mb-8 inline-block" style="color:var(--muted-foreground)">← Home</a>
  <p class="text-xs uppercase tracking-widest mb-2" style="color:var(--accent)">Testimonials</p>
  <h1 class="font-display text-4xl font-light mb-2">What our partners say</h1>
  <p class="text-sm mb-12 max-w-xl" style="color:var(--muted-foreground)">Head chefs and owners on cooking with produce traced back to the farm.</p>
  <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach (testimonials() as $t): ?>
    <figure class="border p-7 flex flex-col gap-6" style="border-color:var(--border);background:var(--card)">
      <blockquote class="font-display text-lg font-light leading-snug">“<?= e($t['quote']) ?>”</blockquote>
      <figcaption class="mt-auto flex items-center gap-3">
        <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold shrink-0" style="background:var(--primary);color:var(--primary-foreground)"><?= e(mb_substr($t['name'], 0, 1)) ?></div>
        <div>
          <p class="text-sm font-medium"><?= e($t['name']) ?></p>
          <p class="text-xs" style="color:var(--muted-foreground)"><?= e($t['role']) ?>, <?= e($t['business']) ?></p>
        </div>
      </figcaption>
    </figure>
    <?php endforeach; ?>
  </div>
</div>
<?php include __DIR__ . '/layout/footer.php'; ?>