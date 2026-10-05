-- Clean up old dummy data to ensure high-quality catalogue
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE plan_itinerary;
TRUNCATE TABLE plan_highlights;
TRUNCATE TABLE plan_images;
TRUNCATE TABLE enquiries;
TRUNCATE TABLE reservations;
TRUNCATE TABLE packages;
TRUNCATE TABLE destinations;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. DESTINATIONS
INSERT INTO destinations (destination_id, destination_name, slug, country, state, description, best_season, image, featured, seo_title, seo_description, status) VALUES
(1, 'Rajasthan', 'rajasthan', 'India', 'Rajasthan', 'The land of kings, Rajasthan is a vibrant state known for its majestic forts, grand palaces, and the sweeping Thar Desert. It offers a rich tapestry of history, culture, and architecture.', 'October - March', NULL, 1, 'Discover Rajasthan - Land of Kings', 'Explore the royal state of Rajasthan. Majestic forts, palaces, and vibrant culture await you.', 'active'),
(2, 'Kerala', 'kerala', 'India', 'Kerala', 'Known as God''s Own Country, Kerala is famous for its serene backwaters, palm-lined beaches, lush hill stations, and rich wildlife.', 'September - March', NULL, 1, 'Discover Kerala - God''s Own Country', 'Experience the serene backwaters, beaches, and hill stations of Kerala.', 'active'),
(3, 'Goa', 'goa', 'India', 'Goa', 'A coastal paradise with a blend of Indian and Portuguese cultures, offering pristine beaches, vibrant nightlife, and laid-back villages.', 'November - February', NULL, 1, 'Discover Goa - Coastal Paradise', 'Relax on the pristine beaches of Goa, a perfect blend of culture and relaxation.', 'active'),
(4, 'Himachal Pradesh', 'himachal-pradesh', 'India', 'Himachal Pradesh', 'A spectacular northern state offering breathtaking Himalayan landscapes, hill stations like Shimla and Manali, and rich Tibetan culture.', 'March - June / September - December', NULL, 1, 'Discover Himachal Pradesh - Mountain Escapes', 'Breathtaking Himalayan landscapes and serene hill stations in Himachal Pradesh.', 'active'),
(5, 'Gujarat', 'gujarat', 'India', 'Gujarat', 'A state of diverse landscapes, from the white salt desert of Kutch to the home of Asiatic lions in Gir, rich in heritage and culture.', 'October - March', NULL, 0, 'Discover Gujarat - Vibrant Heritage', 'Explore the diverse landscapes and rich cultural heritage of Gujarat.', 'active');

-- 2. PACKAGES (Travel Plans)

-- RAJASTHAN
INSERT INTO packages (package_id, package_code, package_name, slug, destination_id, plan_type, duration, duration_days, price, price_type, price_note, maximum_capacity, description, included_services, excluded_services, image, featured, display_order, best_time, seo_title, seo_description, status) VALUES
(1, 'RAJ-DAY-01', 'Jaipur Heritage Day Experience', 'jaipur-heritage-day-experience', 1, 'Day Trip', '1 Day', 1, 4999.00, 'Per Person', 'Final quote depends on selected services and availability.', 20, 'A carefully curated day trip through the Pink City of Jaipur. Experience the grandeur of Amber Fort, the iconic Hawa Mahal, and the Royal City Palace.', 'Guided tour\nPrivate vehicle\nEntry tickets', 'Meals\nPersonal expenses', NULL, 0, 1, 'October - March', 'Jaipur Heritage Day Experience - Day Trip', 'Explore Jaipur in a day with our curated heritage experience.', 'active'),
(2, 'RAJ-BAS-01', 'Rajasthan Highlights', 'rajasthan-highlights', 1, 'Basic', '5 Days / 4 Nights', 5, 12999.00, 'Per Person', 'Starting price based on selected hotel category and standard occupancy.', 20, 'A perfect introduction to Rajasthan, covering the essential highlights of Jaipur, Jodhpur, and Udaipur. Experience the rich culture and majestic architecture of India''s royal state.', 'Standard Accommodation\nBreakfast\nPrivate transfers\nSightseeing', 'Flights\nLunches & Dinners\nEntry fees', NULL, 0, 2, 'October - March', 'Rajasthan Highlights - 5 Days', 'Experience the essential highlights of Jaipur, Jodhpur, and Udaipur.', 'active'),
(3, 'RAJ-PREM-01', 'Royal Rajasthan Escape', 'royal-rajasthan-escape', 1, 'Premium', '7 Days / 6 Nights', 7, 24999.00, 'Per Person', 'Final pricing varies by travel dates, hotel availability, group size and selected services.', 10, 'A carefully designed premium journey through Jaipur, Jodhpur, Jaisalmer and Udaipur. Stay in heritage properties and experience the royal lifestyle of Rajasthan.', 'Premium Accommodation\nBreakfast & Selected Dinners\nPrivate luxury vehicle\nCurated sightseeing\nHeritage experiences', 'Flights\nPersonal expenses\nTravel insurance', NULL, 1, 3, 'October - March', 'Royal Rajasthan Escape - Premium Journey', 'A premium journey through Jaipur, Jodhpur, Jaisalmer, and Udaipur staying in heritage properties.', 'active'),
(4, 'RAJ-FAM-01', 'Rajasthan Family Discovery', 'rajasthan-family-discovery', 1, 'Family', '6 Days / 5 Nights', 6, 21999.00, 'Per Person', 'Final price depends on travel dates, hotel category, group size and availability.', 15, 'A balanced itinerary perfect for families, offering comfortable travel, engaging cultural experiences, and plenty of leisure time to relax.', 'Family Rooms\nBreakfast\nComfortable transfers\nFamily-friendly sightseeing', 'Flights\nPersonal expenses', NULL, 0, 4, 'October - March', 'Rajasthan Family Discovery - 6 Days', 'A family-friendly journey through Rajasthan with engaging cultural experiences.', 'active');

-- KERALA
INSERT INTO packages (package_id, package_code, package_name, slug, destination_id, plan_type, duration, duration_days, price, price_type, price_note, maximum_capacity, description, included_services, excluded_services, image, featured, display_order, best_time, seo_title, seo_description, status) VALUES
(5, 'KER-BAS-01', 'Kerala Backwater Escape', 'kerala-backwater-escape', 2, 'Basic', '5 Days / 4 Nights', 5, 17999.00, 'Per Person', 'Starting price based on selected hotel category and standard occupancy.', 20, 'Discover the magic of God''s Own Country. From the misty tea gardens of Munnar to the serene backwaters of Alleppey.', 'Comfort Accommodation\nBreakfast\nHouseboat stay (1 night)\nPrivate transfers', 'Flights\nPersonal expenses', NULL, 0, 1, 'September - March', 'Kerala Backwater Escape - 5 Days', 'Discover the magic of Kerala, from Munnar to Alleppey backwaters.', 'active'),
(6, 'KER-PREM-01', 'Kerala Signature Escape', 'kerala-signature-escape', 2, 'Premium', '7 Days / 6 Nights', 7, 29999.00, 'Per Person', 'Final pricing varies by travel dates, hotel availability, group size and selected services.', 10, 'Our signature Kerala experience featuring premium resorts, a luxury private houseboat, and curated local experiences.', 'Premium Accommodation\nLuxury Houseboat\nBreakfast & Dinner\nPrivate transfers\nCurated experiences', 'Flights\nPersonal expenses\nTravel insurance', NULL, 1, 2, 'September - March', 'Kerala Signature Escape - Premium Journey', 'Our signature Kerala experience featuring premium resorts and luxury houseboat.', 'active');

-- GOA
INSERT INTO packages (package_id, package_code, package_name, slug, destination_id, plan_type, duration, duration_days, price, price_type, price_note, maximum_capacity, description, included_services, excluded_services, image, featured, display_order, best_time, seo_title, seo_description, status) VALUES
(7, 'GOA-BAS-01', 'Goa Beach Escape', 'goa-beach-escape', 3, 'Basic', '4 Days / 3 Nights', 4, 11999.00, 'Per Person', 'Starting price based on selected hotel category and standard occupancy.', 20, 'A relaxing coastal getaway combining the vibrant energy of North Goa with the serene beaches of South Goa.', 'Accommodation\nBreakfast\nAirport transfers', 'Flights\nMeals not mentioned', NULL, 0, 1, 'November - February', 'Goa Beach Escape - 4 Days', 'A relaxing coastal getaway in North and South Goa.', 'active');

-- HIMACHAL PRADESH
INSERT INTO packages (package_id, package_code, package_name, slug, destination_id, plan_type, duration, duration_days, price, price_type, price_note, maximum_capacity, description, included_services, excluded_services, image, featured, display_order, best_time, seo_title, seo_description, status) VALUES
(8, 'HIM-BAS-01', 'Shimla & Manali Escape', 'shimla-manali-escape', 4, 'Basic', '6 Days / 5 Nights', 6, 18999.00, 'Per Person', 'Starting price based on selected hotel category and standard occupancy.', 20, 'A classic Himalayan journey through the pine-scented hills of Shimla and the breathtaking valleys of Manali.', 'Accommodation\nBreakfast & Dinner\nPrivate vehicle for sightseeing', 'Flights/Trains\nEntry fees to monuments', NULL, 1, 1, 'March - June', 'Shimla & Manali Escape - 6 Days', 'A classic Himalayan journey through Shimla and Manali.', 'active');

-- 3. PLAN HIGHLIGHTS
INSERT INTO plan_highlights (package_id, highlight_text, display_order) VALUES
(3, 'Private luxury transfers', 1),
(3, 'Heritage hotel stays', 2),
(3, 'Desert sunset experience', 3),
(3, 'Curated palace tours', 4),
(6, 'Premium resort stays', 1),
(6, 'Luxury private houseboat', 2),
(6, 'Tea plantation walks', 3),
(6, 'Ayurvedic wellness consultation', 4);

-- 4. PLAN ITINERARY (Example for Royal Rajasthan Escape)
INSERT INTO plan_itinerary (package_id, day_number, title, description, morning, afternoon, evening, overnight) VALUES
(3, 1, 'Arrival in Jaipur', 'Welcome to the Pink City. Upon arrival, our representative will transfer you to your heritage hotel.', NULL, 'Check-in and relax at your heritage hotel.', 'Explore the local vibrant markets of Jaipur.', 'Overnight stay at heritage hotel in Jaipur.'),
(3, 2, 'The Grandeur of Jaipur', 'A full day exploring the architectural marvels of Jaipur.', 'Visit the majestic Amber Fort, riding up the hill or walking.', 'Explore the City Palace and the iconic Hawa Mahal.', 'Optional sunset view from Nahargarh Fort.', 'Overnight stay in Jaipur.'),
(3, 3, 'Journey to Jodhpur', 'Drive to the Blue City, Jodhpur.', 'Scenic drive through the Aravalli hills to Jodhpur.', 'Check-in and visit the imposing Mehrangarh Fort.', 'Walk through the blue-painted streets of the old city.', 'Overnight stay at a premium property in Jodhpur.'),
(3, 4, 'The Golden City of Jaisalmer', 'Travel to the heart of the Thar Desert.', 'Drive to Jaisalmer, known as the Golden City.', 'Check-in to your desert camp or hotel.', 'Enjoy a camel safari and a spectacular desert sunset.', 'Overnight stay in Jaisalmer.'),
(3, 5, 'Jaisalmer Fort and Havelis', 'Explore the living fort and intricate mansions.', 'Visit Jaisalmer Fort, one of the few living forts in the world.', 'Explore Patwon Ki Haveli and Nathmal Ki Haveli.', 'Relaxation time.', 'Overnight stay in Jaisalmer.'),
(3, 6, 'The City of Lakes, Udaipur', 'Drive to the romantic city of Udaipur.', 'Travel to Udaipur via the beautiful Ranakpur Jain Temples.', 'Check-in to your lakeside or heritage hotel.', 'Enjoy a serene boat ride on Lake Pichola at sunset.', 'Overnight stay in Udaipur.'),
(3, 7, 'Udaipur Sightseeing & Departure', 'Final day exploring Udaipur before departure.', 'Visit the grand City Palace and Jagdish Temple.', 'Explore Saheliyon Ki Bari (Garden of the Maidens).', 'Transfer to the airport for your onward journey.', NULL);

--
-- Table structure for table settings
--

DROP TABLE IF EXISTS settings;
CREATE TABLE settings (
  setting_key varchar(100) NOT NULL,
  setting_value text DEFAULT NULL,
  PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table settings
--

INSERT INTO settings (setting_key, setting_value) VALUES
('address', 'SBR, Ahmedabad, Gujarat, India'),
('company_name', 'Patel Travels'),
('email', 'info@pateltravels.local'),
('facebook', 'https://facebook.com/'),
('footer_description', 'Thoughtfully designed journeys, unforgettable destinations and travel experiences created around the way you want to explore.'),
('google_maps', 'https://maps.google.com/'),
('instagram', 'https://instagram.com/'),
('phone', '+91 9876 543 210'),
('tagline', 'Curated Travel Experiences'),
('whatsapp', '+91 9876 543 210'),
('working_hours', 'Mon - Sat, 10:00 AM - 6:00 PM');
CREATE TABLE plan_inclusions (
  id int(11) NOT NULL AUTO_INCREMENT,
  package_id int(11) NOT NULL,
  	ype enum('inclusion','exclusion') NOT NULL DEFAULT 'inclusion',
  content text NOT NULL,
  display_order int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
UPDATE packages SET image = 'https://images.unsplash.com/photo-1599661559814-c8c3faee9e0f?auto=format&fit=crop&w=1200&q=80' WHERE package_id = 1;
UPDATE packages SET image = 'https://images.unsplash.com/photo-1590766940554-63897b6efcc9?auto=format&fit=crop&w=1200&q=80' WHERE package_id = 2;
UPDATE packages SET image = 'https://images.unsplash.com/photo-1477587458883-47145ed94245?auto=format&fit=crop&w=1200&q=80' WHERE package_id = 3;
UPDATE packages SET image = 'https://images.unsplash.com/photo-1588619525203-125fc73397bd?auto=format&fit=crop&w=1200&q=80' WHERE package_id = 4;
UPDATE packages SET image = 'https://images.unsplash.com/photo-1602216056096-3b40cc0c9944?auto=format&fit=crop&w=1200&q=80' WHERE package_id = 5;
UPDATE packages SET image = 'https://images.unsplash.com/photo-1623953580521-49b5c3ff21f3?auto=format&fit=crop&w=1200&q=80' WHERE package_id = 6;
UPDATE packages SET image = 'https://images.unsplash.com/photo-1512343879784-a960bf40e7f2?auto=format&fit=crop&w=1200&q=80' WHERE package_id = 7;
UPDATE packages SET image = 'https://images.unsplash.com/photo-1626618451845-d850257e8eb2?auto=format&fit=crop&w=1200&q=80' WHERE package_id = 8;

UPDATE destinations SET image = 'https://images.unsplash.com/photo-1477587458883-47145ed94245?auto=format&fit=crop&w=1200&q=80' WHERE destination_id = 1;
UPDATE destinations SET image = 'https://images.unsplash.com/photo-1602216056096-3b40cc0c9944?auto=format&fit=crop&w=1200&q=80' WHERE destination_id = 2;
UPDATE destinations SET image = 'https://images.unsplash.com/photo-1512343879784-a960bf40e7f2?auto=format&fit=crop&w=1200&q=80' WHERE destination_id = 3;
UPDATE destinations SET image = 'https://images.unsplash.com/photo-1626618451845-d850257e8eb2?auto=format&fit=crop&w=1200&q=80' WHERE destination_id = 4;
UPDATE destinations SET image = 'https://images.unsplash.com/photo-1623953580521-49b5c3ff21f3?auto=format&fit=crop&w=1200&q=80' WHERE destination_id = 5;
