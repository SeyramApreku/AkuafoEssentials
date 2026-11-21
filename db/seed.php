<?php
// Run once after importing schema.sql:  php db/seed.php   (or open /db/seed.php in the browser)
require_once __DIR__ . '/../core/core.php';
$db = new Database();
$j = fn($v) => json_encode($v, JSON_UNESCAPED_UNICODE);

$products = [
 [1,'White Yam','Dua Kor',22,'per tuber (~1.5kg)','Tubers & Roots','Brong-Ahafo','Kofi Asante','photo-1567306226416-28f0efdc88ce','Harvested in the dry season from the rich loam soils of Techiman. Dense, floury flesh, ideal for ampesi or pounded yam.','In Season'],
 [2,'Ripe Plantain','Aburokyire Borɔdɔ',8,'per hand (~5 fingers)','Fruits & Plantains','Ashanti','Abena Mensah','photo-1636992226967-d5d83d9ccde2','Sweet and deeply golden. Grown in the shaded plots of Ejisu under organic conditions.','Bestseller'],
 [3,'Kontomire (Cocoyam Leaves)','Kontomire',6,'per large bunch','Leafy Greens','Eastern Region','Kwame Frimpong','photo-1515543904379-3d757afe72e4','Tender young leaves from Koforidua hillside gardens. Rich in iron and folate.',null],
 [4,'Garden Eggs','Ntrowa',10,'per kilo','Vegetables','Volta Region','Ama Dzorlali','photo-1618512496248-a07fe83aa8cb','Petite white and purple varieties grown along the Volta riverbanks.','Small Batch'],
 [5,'Scotch Bonnet Peppers','Kpakpo Shito',9,'per 500g','Peppers & Spices','Upper East','Yakubu Ibrahim','photo-1583119022894-919a68a3d0e3','Fiery, fruity, and fragrant. Dried slowly in the Bolgatanga sun.',null],
 [6,'Fresh Tomatoes','Tomati',12,'per kilo','Vegetables','Brong-Ahafo','Kofi Asante','photo-1607305387299-a3d9611cd469','Vine-ripened under the Techiman sun. Deep red, rich in flavour.',null],
 [7,'Dawadawa (Locust Bean)','Dawadawa',14,'per 200g pack','Peppers & Spices','Northern Region','Alhassan Dauda','photo-1601493700631-2b16ec4b4716','Fermented locust beans from Tamale. Deep umami, earthy funk.','Heritage'],
 [8,'Cocoyam (Taro)','Mankani',18,'per 2kg bundle','Tubers & Roots','Central Region','Efua Boateng','photo-1574484284002-952d92456975','Grown in the moist soils near Cape Coast. Starchy and slightly sweet.',null],
];
foreach ($products as $p) $db->execute('REPLACE INTO products VALUES (?,?,?,?,?,?,?,?,?,?,?)', $p);

$boxes = [
 ['veg-box','The Veg Box','Seasonal Ghanaian vegetables, every week','photo-1627989147125-a004d05946d3','#2B4A12',['Kontomire','Garden Eggs','Okra','Fresh Tomatoes','Garden Pepper','Spring Onions','Garden Egg Leaf'],'Contents rotate with the season. You will be notified of any substitutions.',
   [['Small','1-2 people',68,'5-6 items'],['Medium','3-4 people',98,'8-9 items'],['Large','5+ people',138,'12-13 items']]],
 ['fruit-box','The Fruit Box','Tropical and local fruits, freshly harvested','photo-1665904285523-47c0a6fdfc0e','#B24008',['Ripe Plantain','Pawpaw','Pineapple','Watermelon','Banana','Tangerines','Avocado'],'All fruit harvested to order. No cold storage, picked and packed same day.',
   [['Small','1-2 people',58,'4-5 items'],['Medium','3-4 people',85,'7-8 items'],['Large','5+ people',118,'10-12 items']]],
 ['staples-box','Ghana Staples Box','The essentials that anchor every Ghanaian kitchen','photo-1635341083777-5f93a755e916','#6B6050',['White Yam or Cocoyam','Ripe & Green Plantain','Cassava','Kontomire','Fresh Tomatoes','Scotch Bonnets','Onions','Dawadawa'],'Built around the staples, customisable for yam-forward or plantain-forward households.',
   [['Small','1-2 people',88,'6-7 items'],['Medium','3-4 people',125,'9-10 items'],['Large','5+ people',168,'13-15 items']]],
];
foreach ($boxes as $b) {
    $db->execute('REPLACE INTO boxes VALUES (?,?,?,?,?,?,?)', [$b[0],$b[1],$b[2],$b[3],$b[4],$j($b[5]),$b[6]]);
    foreach ($b[7] as $i => $s) $db->execute('INSERT INTO box_sizes (box_id,idx,label,description,price,items) VALUES (?,?,?,?,?,?)', [$b[0],$i,...$s]);
}

$hampers = [
 ['christmas-hamper','Christmas Harvest Hamper','Christmas',320,'7 days','photo-1705088293220-c1a22b5d214a','A full festive box for the Ghanaian Christmas table. Everything you need from dawn kelewele to evening light soup.',['White Yam (3kg)','Ripe Plantain (2 hands)','Cocoyam (2kg)','Kontomire (3 bunches)','Fresh Tomatoes (2kg)','Scotch Bonnets (500g)','Garden Eggs (1kg)','Dawadawa (400g)','Onions (1kg)','Spring Onions & Ginger'],'8-12 people','Festive'],
 ['wedding-bulk','Wedding & Events Pack','Weddings & Events',480,'14 days','photo-1649551895001-85125e4dd5bd','Bulk-packed produce for wedding receptions, outdoorings, and large family gatherings. Coordinated delivery to your venue.',['White Yam (10kg)','Plantain (4 hands)','Cassava (5kg)','Fresh Tomatoes (5kg)','Peppers (1.5kg)','Garden Eggs (3kg)','Kontomire (6 bunches)','Onions (3kg)','Dawadawa (500g)','Palm Nuts (2kg)'],'30-50 people','Bulk'],
 ['family-gathering','Family Gathering Box','Family Gatherings',210,'5 days','photo-1628690570318-9d1e75e4f590','For Sunday gatherings, naming ceremonies, and homecomings. A curated selection sized for a big household table.',['White Yam (2kg)','Ripe Plantain (1 hand)','Kontomire (2 bunches)','Garden Eggs (1kg)','Fresh Tomatoes (1.5kg)','Scotch Bonnets (300g)','Onions (500g)','Spring Onions','Dawadawa (200g)'],'10-20 people',null],
];
foreach ($hampers as $h) { $h[7] = $j($h[7]); $db->execute('REPLACE INTO hampers VALUES (?,?,?,?,?,?,?,?,?,?)', $h); }

$bulk = [
 ['bulk-tomato','Tomato Crate','10kg, Techiman',95,'photo-1607305387299-a3d9611cd469'],
 ['bulk-plantain','Plantain Crate','20 hands, Ejisu',130,'photo-1636992226967-d5d83d9ccde2'],
 ['bulk-yam','Yam Sack','25kg, Techiman',280,'photo-1567306226416-28f0efdc88ce'],
 ['bulk-pepper','Pepper Sack','5kg, Bolgatanga',75,'photo-1583119022894-919a68a3d0e3'],
];
foreach ($bulk as $b) $db->execute('REPLACE INTO bulk_items VALUES (?,?,?,?,?)', $b);

$recipes = [
 [1,'Kontomire Stew','Palava Sauce','45 min','Easy',4,'photo-1665332195309-9d75071138f0',
  ['2 large bunches kontomire','500g smoked fish or tilapia','3 garden eggs','4 scotch bonnets','2 medium onions','100ml palm oil','Salt to taste','Dawadawa (optional)'],
  ['Wash and roughly chop the kontomire leaves. Boil for 10 minutes until tender, then drain and roughly crush with a wooden spoon.','Roast or grill the garden eggs until the skin is charred. Peel and mash coarsely.','Blend the scotch bonnets and one onion. Fry in palm oil over medium heat for 8-10 minutes until the oil rises.','Add the crushed kontomire, mashed garden egg, and flaked smoked fish. Stir well.','Season with salt and a pinch of dawadawa. Simmer for 15 minutes. Serve over boiled cocoyam or rice.'],
  ['Kontomire','Garden Eggs','Scotch Bonnet Peppers','Dawadawa'],['Vegetarian-adaptable','Quick','Traditional']],
 [2,'Kelewele','Spiced Fried Plantain','20 min','Easy',2,'photo-1665332111329-dd9d7fa46c74',
  ['3 ripe plantains','1 tsp ground ginger','1 tsp ground anise pepper (prekese)','½ tsp cayenne or blended scotch bonnet','½ tsp salt','Neutral oil for frying'],
  ['Peel and cut plantains into rough cubes or diagonal slices about 2cm thick.','Mix the ginger, pepper, cayenne and salt. Toss the plantain pieces in the spice mix until evenly coated.','Heat oil in a heavy pan to 175°C. Fry plantain in batches for 3-4 minutes until deep golden and caramelised at the edges.','Drain on paper and serve immediately. Best eaten hot with groundnuts or as a side to grilled fish.'],
  ['Ripe Plantain','Scotch Bonnet Peppers'],['Vegan','Quick','Street Food']],
 [3,'Yam Pottage','Mpotompoto','50 min','Medium',4,'photo-1665554837563-3782d21a676b',
  ['1 medium white yam (~800g)','200ml coconut milk','3 fresh tomatoes','1 large onion','2 scotch bonnets','2 tbsp palm oil','Spring onions to garnish','Salt and pepper'],
  ['Peel and cut yam into 3cm chunks. Cover with water and boil for 20 minutes until just tender.','Blend the tomatoes, onion and scotch bonnets into a coarse paste.','In a separate pan, heat palm oil and fry the blended tomato mix for 10 minutes until thick.','Drain the yam. Add the tomato sauce and coconut milk to the yam pot.','Mash some of the yam against the side of the pot, rough and porridge-like with some chunks intact.','Simmer together for 10 minutes. Season, garnish with spring onion.'],
  ['White Yam','Fresh Tomatoes','Scotch Bonnet Peppers'],['Vegan','Filling','Traditional']],
 [4,'Garden Egg Stew','Ntrowa Nkwan','35 min','Easy',3,'photo-1664992960082-0ea299a9c53e',
  ['600g garden eggs','4 medium fresh tomatoes','1 large onion','2 scotch bonnets','100ml groundnut oil','Smoked herring (optional)','Salt','Dawadawa'],
  ['Roast garden eggs directly over a gas flame until the skin blisters. Cool, peel and chop coarsely.','Blend tomatoes, half the onion and scotch bonnets. Fry in groundnut oil with the remaining sliced onion for 12 minutes.','Add the chopped garden eggs and flaked smoked herring. Cook for another 10 minutes.','Finish with a small piece of dawadawa, crushed. Serve with rice or boiled plantain.'],
  ['Garden Eggs','Fresh Tomatoes','Scotch Bonnet Peppers','Dawadawa'],['Quick','Vegetarian-adaptable','Traditional']],
];
foreach ($recipes as $r) { foreach ([7,8,9,10] as $k) $r[$k] = $j($r[$k]); $db->execute('REPLACE INTO recipes VALUES (?,?,?,?,?,?,?,?,?,?,?)', $r); }

$farmers = [
 [1,'Kofi Asante','Brong-Ahafo Region','Techiman','photo-1740000649192-7fa4736c4c08',['White Yam','Fresh Tomatoes'],'Kofi has farmed the same plot outside Techiman for 22 years. His yam mounds are planted in October and harvested in the dry season when the starches are at their most concentrated. He farms without synthetic fertiliser, rotating with legumes to restore nitrogen between seasons.',['Pesticide-free','Soil-tested annually'],4.5,'Oct - Feb (Yam)'],
 [2,'Abena Mensah','Ashanti Region','Ejisu','photo-1686146711957-04aec031396b',['Ripe Plantain','Kontomire'],'Abena intercropped her two-hectare plot with plantain and cocoyam under the partial shade of oil palms, a technique passed down through three generations. She sells directly into Akuafo, getting a fairer price by cutting out the Kumasi market middleman.',['Organic (pending)'],2,'Year-round'],
 [3,'Yakubu Ibrahim','Upper East Region','Bolgatanga','photo-1740000649300-c5d17f6a3cc4',['Scotch Bonnet Peppers','Fresh Tomatoes'],"The Upper East is Ghana's pepper heartland. Yakubu works 3 hectares of irrigated land near the White Volta, growing the fiery kpakpo shito that defines Ghanaian cooking. He sun-dries a portion of each harvest for deeper flavour.",['Pesticide-free','Water-certified'],3,'Dec - March (Peak)'],
 [4,'Ama Dzorlali','Volta Region','Ho','photo-1665904285523-47c0a6fdfc0e',['Garden Eggs'],'Ama grows both the white and small purple garden egg varieties along the Volta River basin near Ho. The fertile alluvial soils produce fruit with a distinctive mild bitterness that chefs seek out. She joined Akuafo in 2022 and has since tripled her plot.',['Pesticide-free'],1.2,'Sept - Dec (White); Year-round (Purple)'],
 [5,'Alhassan Dauda','Northern Region','Tamale','photo-1625191824758-e00b2c51f388',['Dawadawa (Locust Bean)'],"Dawadawa is one of the oldest preserved foods in West Africa. Alhassan's family has made it for four generations using locust beans gathered from wild néré trees outside Tamale. Boiled, fermented for three days, then sun-dried.",['Wild-harvested','Traditional process'],null,'June - September (Harvest)'],
];
foreach ($farmers as $f) { $f[5] = $j($f[5]); $f[7] = $j($f[7]); $db->execute('REPLACE INTO farmers VALUES (?,?,?,?,?,?,?,?,?,?)', $f); }

echo "Seeded.\n";
