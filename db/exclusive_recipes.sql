-- =====================================================================
-- Akuafo & Essentials: Exclusive members' recipes from partner restaurants
-- Easiest: Admin > Menu > Database Update > Update database.
-- Or in phpMyAdmin: click your database first, then SQL tab, paste, Go. Safe to run more than once.
-- Needs membership_benefits.sql and partner_recipes.sql first (Database Update runs them in order).
-- Photos and wording can be changed any time in Admin > Recipes.
-- =====================================================================

-- Recipes number themselves (like products and farmers), so new ones can be added.
SET @n = (SELECT COALESCE(MAX(id), 0) + 1 FROM aku_recipes);
UPDATE aku_recipes SET id = @n WHERE id = 0;
SET @q = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'aku_recipes'
             AND column_name = 'id' AND extra LIKE '%auto_increment%') = 0,
  'ALTER TABLE aku_recipes MODIFY id INT NOT NULL AUTO_INCREMENT', 'DO 0');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;

-- Four members-only recipes, one from each partner kitchen. Each is added only if it isn't there yet.
INSERT INTO aku_recipes (name, subtitle, time, difficulty, serves, image, ingredients, method, produce, tags, members_only, partner)
SELECT * FROM (SELECT
  'Abenkwan with Smoked Mackerel' AS name, 'Palm Nut Soup' AS subtitle, '1 hr 30 min' AS time, 'Medium' AS difficulty, 6 AS serves,
  'photo-1625191824758-e00b2c51f388' AS image,
  '["1kg palm nut concentrate (or 2kg fresh palm nuts)","2 smoked mackerel, flaked","500g goat or chicken, cut small","4 garden eggs","3 fresh tomatoes","2 onions","3 scotch bonnets","1 thumb ginger","Salt to taste"]' AS ingredients,
  '["Season the meat with one blended onion, ginger and salt. Steam in its own juices for 10 minutes.","Add 1.5 litres of water with the whole garden eggs, tomatoes and scotch bonnets. Boil for 15 minutes, then lift them out and blend smooth.","Stir the palm nut concentrate into the pot with the blended vegetables. Simmer uncovered for 40 minutes, skimming the red oil that rises.","Add the flaked mackerel for the last 10 minutes so it stays in pieces.","Taste for salt. Serve with fufu, omo tuo or boiled rice. Adom Kitchen finishes each bowl with a spoon of the skimmed oil."]' AS method,
  '["Garden Eggs","Fresh Tomatoes","Scotch Bonnet Peppers"]' AS produce,
  '["Members","Signature","Traditional"]' AS tags, 1 AS members_only, 'Adom Kitchen' AS partner
) r WHERE NOT EXISTS (SELECT 1 FROM aku_recipes WHERE name = 'Abenkwan with Smoked Mackerel');

INSERT INTO aku_recipes (name, subtitle, time, difficulty, serves, image, ingredients, method, produce, tags, members_only, partner)
SELECT * FROM (SELECT
  'Red Red with Kelewele Crumb' AS name, 'Black-eyed Bean Stew' AS subtitle, '1 hr' AS time, 'Easy' AS difficulty, 4 AS serves,
  'photo-1607305387299-a3d9611cd469' AS image,
  '["400g black-eyed beans, soaked overnight","120ml red palm oil","2 onions, finely chopped","4 fresh tomatoes, blended","2 scotch bonnets","1 tbsp ginger and garlic paste","2 ripe plantains","1 tsp ground ginger","Salt"]' AS ingredients,
  '["Boil the soaked beans for 35 to 40 minutes until soft but still whole. Drain, keeping a cup of the cooking water.","Warm the palm oil and fry the onions until golden. Add the ginger and garlic paste, then the blended tomatoes and scotch bonnets. Cook down for 15 minutes.","Fold in the beans with a splash of cooking water and simmer for 10 minutes. Season with salt.","Dice the plantains very small, toss with ground ginger and salt, and fry until dark and crisp.","Serve the beans topped with the crisp plantain crumb. This is the Sunday staff lunch at Sankofa Table."]' AS method,
  '["Ripe Plantain","Fresh Tomatoes","Scotch Bonnet Peppers"]' AS produce,
  '["Members","Vegan","Comfort"]' AS tags, 1 AS members_only, 'Sankofa Table' AS partner
) r WHERE NOT EXISTS (SELECT 1 FROM aku_recipes WHERE name = 'Red Red with Kelewele Crumb');

INSERT INTO aku_recipes (name, subtitle, time, difficulty, serves, image, ingredients, method, produce, tags, members_only, partner)
SELECT * FROM (SELECT
  'Charcoal Tilapia with Fresh Pepper' AS name, 'Volta Lakeside Grill' AS subtitle, '50 min' AS time, 'Medium' AS difficulty, 4 AS serves,
  'photo-1601493700631-2b16ec4b4716' AS image,
  '["4 whole tilapia, cleaned and scored","2 onions","1 thumb ginger","4 cloves garlic","1 tbsp ground prekese (optional)","3 fresh tomatoes","4 scotch bonnets","1 tsp salt","Neutral oil"]' AS ingredients,
  '["Blend one onion, ginger, garlic, prekese, salt and a little oil into a paste. Rub it into the cuts and cavity of each fish. Rest for 20 minutes.","Grill over hot charcoal for 8 to 10 minutes a side, brushing with oil, until the skin is blistered and the flesh flakes.","For the fresh pepper, grind the tomatoes, scotch bonnets and the second onion coarsely in an asanka with a pinch of salt. Do not cook it.","Serve the fish hot with banku and the fresh pepper on the side, the way Volta Bay Lodge serves it on the lakeside deck."]' AS method,
  '["Fresh Tomatoes","Scotch Bonnet Peppers"]' AS produce,
  '["Members","Grill","Signature"]' AS tags, 1 AS members_only, 'Volta Bay Lodge' AS partner
) r WHERE NOT EXISTS (SELECT 1 FROM aku_recipes WHERE name = 'Charcoal Tilapia with Fresh Pepper');

INSERT INTO aku_recipes (name, subtitle, time, difficulty, serves, image, ingredients, method, produce, tags, members_only, partner)
SELECT * FROM (SELECT
  'Suya-Spiced Yam Chips with Shito Mayo' AS name, 'Grill House Side' AS subtitle, '40 min' AS time, 'Easy' AS difficulty, 4 AS serves,
  'photo-1636992226967-d5d83d9ccde2' AS image,
  '["1 medium white yam (about 800g)","2 tbsp suya spice (yaji)","1 tsp salt","Oil for frying","4 tbsp mayonnaise","1 tbsp shito","1 spring onion, sliced"]' AS ingredients,
  '["Peel the yam and cut into thick chips. Boil in salted water for 6 minutes, drain well and let the steam dry them.","Fry the chips at 170 degrees for 6 to 8 minutes until golden and crisp outside.","Toss straight away with the suya spice and a pinch of salt.","Stir the shito into the mayonnaise. Serve the chips scattered with spring onion and the shito mayo for dipping. A Savannah Grill favourite."]' AS method,
  '["White Yam","Scotch Bonnet Peppers"]' AS produce,
  '["Members","Quick","Sharing"]' AS tags, 1 AS members_only, 'Savannah Grill' AS partner
) r WHERE NOT EXISTS (SELECT 1 FROM aku_recipes WHERE name = 'Suya-Spiced Yam Chips with Shito Mayo');
