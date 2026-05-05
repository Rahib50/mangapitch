
SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;
USE mangapitch;


INSERT INTO Users (UserID, Name, Email, Password, Role) VALUES
-- Mangakas
(1,  'Hayao Miyazaki',   'miyazaki@ghibli.com',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mangaka'),
(2,  'Aoi Hiiragi',      'hiiragi@mail.com',        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mangaka'),
(3,  'Tatsuki Fujimoto', 'fujimoto@mail.com',       '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mangaka'),
(4,  'Hajime Isayama',   'isayama@mail.com',        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mangaka'),
(5,  'Makoto Yukimura',  'yukimura@mail.com',       '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mangaka'),
(6,  'Gege Akutami',     'akutami@mail.com',        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mangaka'),
(7,  'Koyoharu Gotouge', 'gotouge@mail.com',        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mangaka'),
(8,  'Hiromu Arakawa',   'arakawa@mail.com',        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mangaka'),
(9,  'Kohei Horikoshi',  'horikoshi@mail.com',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mangaka'),
-- Studios 
(10, 'Studio Ghibli',    'contact@ghibli.jp',       '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Studio'),
(11, 'MAPPA',            'contact@mappa.jp',        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Studio'),
(12, 'ufotable',         'contact@ufotable.jp',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Studio'),
(13, 'Studio Bones',     'contact@bones.jp',        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Studio'),
-- Admin
(14, 'Site Admin',       'admin@mangapitch.com',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin');


INSERT INTO Mangaka (UserID, PortfolioLink) VALUES
(1, 'https://ghibli.jp/miyazaki'),
(2, 'https://ribon.jp/hiiragi'),
(3, 'https://shonenjump.com/fujimoto'),
(4, 'https://shonenjump.com/isayama'),
(5, 'https://afternoonmanga.jp/yukimura'),
(6, 'https://shonenjump.com/akutami'),
(7, 'https://shonenjump.com/gotouge'),
(8, 'https://squareenix.com/arakawa'),
(9, 'https://shonenjump.com/horikoshi');

INSERT INTO Studio (UserID, RegistrationNumber) VALUES
(10, 'JP-STUDIO-1985-GBL'),
(11, 'JP-STUDIO-2011-MPA'),
(12, 'JP-STUDIO-2000-UFT'),
(13, 'JP-STUDIO-1998-BNS');

INSERT INTO Admin (UserID, AdminLevel) VALUES
(14, 3);


INSERT INTO Manga (MangaID, MangakaID, Title, Synopsis, PublishDate) VALUES
(1,  1, 'Nausicaa of the Valley of the Wind',
    'Set in a post-apocalyptic future overrun by a toxic jungle, Princess Nausicaa fights to bridge the divide between humanity and the giant insects that now dominate the earth, seeking coexistence over destruction.',
    '1982-02-07'),
(2,  2, 'Whisper of the Heart',
    'Shizuku Tsukishima, an avid reader, notices every library book she borrows has been previously checked out by the same boy. Their eventual meeting sparks a journey of first love, self-discovery, and creative ambition.',
    '1989-08-22'),
(3,  1, 'My Neighbor Totoro',
    'When two young sisters move to the countryside to be closer to their ailing mother, they encounter magical forest guardians known as Totoro, embarking on gentle and heartfelt childhood adventures.',
    '1988-04-16'),
(4,  3, 'Chainsaw Man',
    'Denji, a debt-ridden devil hunter, merges with his devil dog Pochita to survive a fatal betrayal, becoming the Chainsaw Man. Now working for a government agency, he navigates a brutal world in pursuit of the simplest human desires.',
    '2018-12-03'),
(5,  4, 'Attack on Titan',
    'Humanity cowers behind massive walls to escape Titans, giant creatures that devour people without reason. After his hometown falls, Eren Yeager enlists to fight back and gradually uncovers the devastating truth behind their walled existence.',
    '2009-09-09'),
(6,  5, 'Vinland Saga',
    'Thorfinn, son of a legendary warrior, is consumed by revenge after his father is murdered by the mercenary Askeladd. Raised on the battlefield, he must confront whether a true warrior genuinely has no enemies.',
    '2005-07-13'),
(7,  6, 'Jujutsu Kaisen',
    'After swallowing a cursed finger to save a classmate, Yuji Itadori becomes the vessel of the mighty Curse Ryomen Sukuna. Taken in by a school of Jujutsu Sorcerers, he must consume all of Sukuna\'s fingers before being executed.',
    '2018-03-05'),
(8,  7, 'Demon Slayer',
    'After returning home to find his family slaughtered and his sister Nezuko transformed into a demon, Tanjiro Kamado trains as a Demon Slayer, embarking on a dangerous quest to restore his sister\'s humanity and bring justice to his family.',
    '2016-02-15'),
(9,  8, 'Fullmetal Alchemist',
    'Brothers Edward and Alphonse Elric attempt the forbidden act of human transmutation to revive their mother, each paying a devastating physical price. They journey across the land in search of the Philosopher\'s Stone to reclaim what was lost.',
    '2001-07-12'),
(10, 9, 'My Hero Academia',
    'In a world where most people possess superpowers called Quirks, Izuku Midoriya is born without any. After inheriting a mysterious power from the greatest hero, he enrols in a prestigious hero academy and begins his gruelling path to greatness.',
    '2014-07-07');

-- Schema seed already inserted IDs 1–10
-- (Action, Adventure, Comedy, Drama, Fantasy, Horror, Romance, Sci-Fi, Slice of Life, Thriller)
INSERT INTO Genre (GenreID, GenreName) VALUES
(11, 'Historical'),
(12, 'Supernatural');


INSERT INTO Manga_Genre_Map (MangaID, GenreID) VALUES
-- Nausicaa: Action, Adventure, Fantasy, Sci-Fi
(1,1),(1,2),(1,5),(1,8),
-- Whisper of the Heart: Romance, Drama, Slice of Life
(2,7),(2,4),(2,9),
-- My Neighbor Totoro: Fantasy, Slice of Life, Adventure
(3,5),(3,9),(3,2),
-- Chainsaw Man: Action, Horror, Supernatural
(4,1),(4,6),(4,12),
-- Attack on Titan: Action, Drama, Fantasy, Horror
(5,1),(5,4),(5,5),(5,6),
-- Vinland Saga: Action, Adventure, Historical, Drama
(6,1),(6,2),(6,11),(6,4),
-- Jujutsu Kaisen: Action, Fantasy, Horror, Supernatural
(7,1),(7,5),(7,6),(7,12),
-- Demon Slayer: Action, Fantasy, Historical, Supernatural
(8,1),(8,5),(8,11),(8,12),
-- Fullmetal Alchemist: Action, Adventure, Fantasy, Drama
(9,1),(9,2),(9,5),(9,4),
-- My Hero Academia: Action, Comedy, Fantasy
(10,1),(10,3),(10,5);


INSERT INTO Analytics (AnalyticsID, MangaID, TotalViews, VolumesSold) VALUES
(1,  1,   450000,  12000),
(2,  2,   280000,   8500),
(3,  3,   520000,  15000),
(4,  4,  2100000,  45000),
(5,  5,  3500000,  78000),
(6,  6,   890000,  22000),
(7,  7,  2800000,  62000),
(8,  8,  3200000,  95000),
(9,  9,  1500000,  38000),
(10,10,  2400000,  55000);


INSERT INTO Bids (BidID, MangaID, StudioID, BidAmount, Status) VALUES
-- Accepted (each has a contract)
(1,  1,  10,  500000.00, 'Accepted'),   -- Nausicaa     → Ghibli
(2,  2,  10,  350000.00, 'Accepted'),   -- Whisper      → Ghibli
(3,  3,  10,  600000.00, 'Accepted'),   -- Totoro       → Ghibli
(4,  4,  11, 1200000.00, 'Accepted'),   -- Chainsaw Man → MAPPA
(5,  5,  11, 2500000.00, 'Accepted'),   -- AoT          → MAPPA
(6,  6,  11,  850000.00, 'Accepted'),   -- Vinland      → MAPPA
(7,  7,  11, 1800000.00, 'Accepted'),   -- JJK          → MAPPA
(8,  8,  12, 2200000.00, 'Accepted'),   -- Demon Slayer → ufotable
(9,  9,  13, 1100000.00, 'Accepted'),   -- FMA          → Bones
(10,10,  13, 1500000.00, 'Accepted'),   -- MHA          → Bones
-- Rejected competing bids
(11, 4,  13,  900000.00, 'Rejected'),   -- Chainsaw Man → Bones  (lost)
(12, 5,  13, 2000000.00, 'Rejected'),   -- AoT          → Bones  (lost)
(13, 8,  11, 1900000.00, 'Rejected'),   -- Demon Slayer → MAPPA  (lost)
-- Pending bids
(14, 7,  12, 1500000.00, 'Pending'),    -- JJK          → ufotable
(15,10,  11, 1200000.00, 'Pending');    -- MHA          → MAPPA


INSERT INTO Contracts (ContractID, BidID, SignedDate, ProductStatus) VALUES
(1,  1, '1989-03-15', 'Released'),
(2,  2, '1989-07-22', 'Released'),
(3,  3, '1988-04-16', 'Released'),
(4,  4, '2022-10-01', 'Released'),
(5,  5, '2013-09-09', 'Released'),
(6,  6, '2023-01-15', 'In-Production'),
(7,  7, '2023-04-01', 'In-Production'),
(8,  8, '2021-01-10', 'Released'),
(9,  9, '2003-10-04', 'Released'),
(10,10, '2023-03-25', 'In-Production');


INSERT INTO Messages (MessageID, SenderID, ReceiverID, MessageText) VALUES
(1,  10, 1,  'Hello Mr. Miyazaki, we are very interested in acquiring the animation rights to Nausicaa. Would you be open to discussing terms?'),
(2,  1,  10, 'Thank you for reaching out. I am open to discussions, but preserving the artistic vision throughout production is non-negotiable for me.'),
(3,  10, 1,  'Absolutely understood. Artistic integrity is our priority. We would like to schedule a meeting to walk you through our production plan.'),
(4,  11, 4,  'We have reviewed Chainsaw Man and believe it is a perfect fit for our studio. Our team is ready to submit a competitive offer.'),
(5,  4,  11, 'Glad to hear it. I have been following MAPPA\'s recent work closely. Please proceed with a formal bid through the platform.'),
(6,  12, 7,  'Jujutsu Kaisen has incredible potential and we feel ufotable\'s visual style would bring it to life. We would love to discuss a partnership.'),
(7,  7,  12, 'Thank you for your interest. I am currently reviewing multiple bids and will respond once a decision has been reached.'),
(8,  13, 9,  'We at Studio Bones are huge fans of My Hero Academia. Our experience with action-driven narratives makes us the ideal production partner.'),
(9,  9,  13, 'I appreciate the enthusiasm. Your previous works speak for themselves. I look forward to reviewing your formal bid.'),
(10, 11, 5,  'Attack on Titan is a generational work. MAPPA would be honoured to bring your vision to the screen. Please give our offer serious consideration.');

SET FOREIGN_KEY_CHECKS = 1;