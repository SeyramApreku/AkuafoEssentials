<footer class="border-t mt-4" style="border-color:var(--border);background:var(--card)">
  <div class="max-w-7xl mx-auto px-6 py-14 grid md:grid-cols-4 gap-10">
    <div class="md:col-span-2">
      <h3 class="font-display text-xl font-light mb-1">Akuafo <span style="color:var(--accent)">&amp;</span> Essentials</h3>
      <p class="text-xs italic mb-3" style="color:var(--accent)">Freshness You Can Trace, From Source to Table</p>
      <p class="text-xs leading-relaxed max-w-xs" style="color:var(--muted-foreground)">Farm-direct produce from across Ghana. Online only. Every item traceable to a named farmer and region.</p>
    </div>
    <div>
      <p class="text-xs uppercase tracking-widest mb-4" style="color:var(--muted-foreground)">Explore</p>
      <nav class="flex flex-col gap-2">
        <?php foreach (array_slice(nav_items(), 1) as $path => $label): ?><a href="<?= url($path) ?>" class="text-xs hover:opacity-60"><?= $label ?></a><?php endforeach; ?>
      </nav>
    </div>
    <div>
      <p class="text-xs uppercase tracking-widest mb-4" style="color:var(--muted-foreground)">Contact</p>
      <p class="text-xs leading-relaxed" style="color:var(--muted-foreground)">akuafoessentials@gmail.com<br>Accra, Ghana<br>Orders dispatched Mon-Sat</p>
    </div>
  </div>
  <div class="border-t px-6 py-5 flex items-center justify-between" style="border-color:var(--border)">
    <p class="text-xs" style="color:var(--muted-foreground)">© <?= date('Y') ?> Akuafo &amp; Essentials</p>
    <p class="text-xs" style="color:var(--muted-foreground)">Made with care. Grown with love.</p>
  </div>
</footer>
<script src="<?= url('js/app.js') ?>"></script>
</body>
</html>
