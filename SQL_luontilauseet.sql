CREATE TABLE asiakas ( 
  id INT PRIMARY KEY, 
  nimi VARCHAR(128) NOT NULL, 
  osoite VARCHAR(128) NOT NULL 
);

INSERT INTO asiakas VALUES (1, 'Jaska Hosunen', 'Susimetsä');
INSERT INTO asiakas VALUES (2, 'Lissu Jokinen', 'Susimetsä');
INSERT INTO asiakas VALUES (3, 'Masa Näsänen', 'Masalantie');
INSERT INTO asiakas VALUES (4, 'Matti Matinpoika', 'Matinkatu');
INSERT INTO asiakas VALUES (5, 'Minna Hiiri', 'Hiirenkolo');


CREATE TABLE tyokohde ( 
  id INT PRIMARY KEY, 
  osoite VARCHAR(128) NOT NULL, 
  asiakas_id INT NOT NULL, 
  FOREIGN KEY (asiakas_id) REFERENCES asiakas(id) 
);

INSERT INTO tyokohde VALUES (1, 'Susimetsä', 1);
INSERT INTO tyokohde VALUES (2, 'Nurmitie', 2);
INSERT INTO tyokohde VALUES (3, 'Puotonkorpi', 3);
INSERT INTO tyokohde VALUES (4, 'Masalantie', 3);
INSERT INTO tyokohde VALUES (5, 'Huitsinneva', 2);
INSERT INTO tyokohde VALUES (6, 'Matinkatu', 4);
INSERT INTO tyokohde VALUES (7, 'Koivukatu', 2);


CREATE TABLE tyosuoritus ( 
  id INT PRIMARY KEY, 
  tyotyyppi VARCHAR(128) NOT NULL, 
  urakkahinta DECIMAL(10,2), 
  tyokohde_id INT NOT NULL, 
  FOREIGN KEY (tyokohde_id) REFERENCES tyokohde(id) 
); 

INSERT INTO tyosuoritus VALUES (1, 'urakka', 100, 1);
INSERT INTO tyosuoritus VALUES (2, 'tunti', null, 2);
INSERT INTO tyosuoritus VALUES (3, 'tunti', null, 3);
INSERT INTO tyosuoritus VALUES (4, 'urakka', 50, 5);
INSERT INTO tyosuoritus VALUES (5, 'tunti', null, 4);



CREATE TABLE lasku ( 
  id INT PRIMARY KEY, 
  valmis BOOLEAN NOT NULL, 
  annettu_pvm DATE NOT NULL, 
  era_pvm DATE NOT NULL, 
  maksettu_pvm DATE, 
  maksettu_status BOOLEAN NOT NULL, 
  asiakas_id INT NOT NULL, 
  tyosuoritus_id INT NOT NULL, 
  FOREIGN KEY (asiakas_id) REFERENCES asiakas(id), 
  FOREIGN KEY (tyosuoritus_id) REFERENCES tyosuoritus(id) 
);

INSERT INTO lasku VALUES (1, true, '2025-10-01', '2025-10-15', null, true, 1, 1);
INSERT INTO lasku VALUES (2, true, '2025-02-01', '2025-02-15', '2025-12-01', true, 2, 2);
INSERT INTO lasku VALUES (3, true, '2026-02-01', '2026-02-15', null, false, 3, 3);
INSERT INTO lasku VALUES (4, true, '2026-03-01', '2026-03-15', null, false, 2, 5);
INSERT INTO lasku VALUES (5, true, '2026-03-01', '2025-03-15', null, false, 3, 4);


CREATE TABLE tyotehtava ( 
  id INT PRIMARY KEY, 
  nimi VARCHAR(128) NOT NULL, 
  tuntihinta DECIMAL(10,2) 
);

INSERT INTO tyotehtava VALUES (1, 'suunnittelu', 55);
INSERT INTO tyotehtava VALUES (2, 'työ', 45);
INSERT INTO tyotehtava VALUES (3, 'aputyö', 35);


CREATE TABLE tyyppi ( 
  nimi VARCHAR(128) PRIMARY KEY, 
  alv_prosentti DECIMAL(5,2) 
); 

INSERT INTO tyyppi VALUES ('Yleinen', 0.24); 
INSERT INTO tyyppi VALUES ('Kirjallisuus', 0.10); 


CREATE TABLE tarvike ( 
  id INT PRIMARY KEY, 
  nimi VARCHAR(128) NOT NULL, 
  merkki VARCHAR(128) NOT NULL, 
  toimittaja VARCHAR(128) NOT NULL, 
  sis_hinta DECIMAL(10,2) NOT NULL, 
  yksikko VARCHAR(128) NOT NULL, 
  varasto INT NOT NULL, 
  tyyppi_nimi VARCHAR(128) NOT NULL, 
  FOREIGN KEY (tyyppi_nimi) REFERENCES tyyppi(nimi) 
); 

INSERT INTO tarvike VALUES (1, 'USB-kaapeli', 'Asus', 'How-data', 4, 'kpl', 500, 'Yleinen');
INSERT INTO tarvike VALUES (2, 'sahkojohto', 'Asus', 'Moponet', 1, 'metri', 200, 'Yleinen');
INSERT INTO tarvike VALUES (3, 'opaskirja', 'Otava', 'Tärsky Pub', 8, 'kpl', 100, 'Kirjallisuus');
INSERT INTO tarvike VALUES (4, 'maakaapeli', 'Maara', 'Moponet', 4, 'metri', 500, 'Yleinen');
INSERT INTO tarvike VALUES (5, 'sahkokeskus', 'Vare', 'Junk Co', 300, 'kpl', 50, 'Yleinen');
INSERT INTO tarvike VALUES (6, 'palohälytin', 'Vare', 'Junk Co', 4, 'kpl', 500, 'Yleinen');
INSERT INTO tarvike VALUES (7, 'pistorasia', 'Vare', 'Moponet', 10, 'kpl', 400, 'Yleinen');
INSERT INTO tarvike VALUES (8, 'termostaatti', 'Baden', 'Moponet', 20, 'kpl', 50, 'Yleinen');
INSERT INTO tarvike VALUES (9, 'valaisin', 'Airam', 'Moponet', 5, 'kpl', 100, 'Yleinen');


CREATE TABLE tehtavat ( 
  tyosuoritus_id INT, 
  tyotehtava_id INT, 
  tunnit INT NOT NULL, 
  alennus DECIMAL(5,2), 
  PRIMARY KEY (tyosuoritus_id, tyotehtava_id), 
  FOREIGN KEY (tyosuoritus_id) REFERENCES tyosuoritus(id), 
  FOREIGN KEY (tyotehtava_id) REFERENCES tyotehtava(id)
); 

INSERT INTO tehtavat VALUES (2, 1, 3, 10);
INSERT INTO tehtavat VALUES (3, 1, 25, 20);
INSERT INTO tehtavat VALUES (3, 2, 7, 10);
INSERT INTO tehtavat VALUES (3, 3, 3, 0);
INSERT INTO tehtavat VALUES (5, 1, 3, 0);
INSERT INTO tehtavat VALUES (5, 2, 12, 0);


CREATE TABLE tarvikkeet ( 
  tyosuoritus_id INT, 
  tarvike_id INT, 
  maara INT NOT NULL, 
  alennus DECIMAL(5,2), 
  PRIMARY KEY (tyosuoritus_id, tarvike_id), 
  FOREIGN KEY (tyosuoritus_id) REFERENCES tyosuoritus(id), 
  FOREIGN KEY (tarvike_id) REFERENCES tarvike(id)
); 

INSERT INTO tarvikkeet VALUES (1, 1, 1, 0);
INSERT INTO tarvikkeet VALUES (2, 2, 3, 10);
INSERT INTO tarvikkeet VALUES (2, 3, 1, 0);
INSERT INTO tarvikkeet VALUES (2, 7, 1, 20);
INSERT INTO tarvikkeet VALUES (3, 4, 100, 10);
INSERT INTO tarvikkeet VALUES (3, 5, 1, 5);
INSERT INTO tarvikkeet VALUES (4, 6, 2, 0);
INSERT INTO tarvikkeet VALUES (5, 2, 3, 0);
INSERT INTO tarvikkeet VALUES (5, 7, 1, 0);


CREATE TABLE lisalasku ( 
  id INT PRIMARY KEY, 
  annettu_pvm DATE NOT NULL, 
  era_pvm DATE NOT NULL, 
  maksettu_pvm DATE, 
  edellinen_id INT, 
  alkp_id INT NOT NULL, 
  FOREIGN KEY (edellinen_id) REFERENCES lisalasku(id), 
  FOREIGN KEY (alkp_id) REFERENCES lasku(id) 
);

INSERT INTO lisalasku VALUES (1, '2025-10-25', '2025-11-10', null, null, 1);
INSERT INTO lisalasku VALUES (2, '2025-11-27', '2025-12-13', '2025-12-01', 1, 1);
INSERT INTO lisalasku VALUES (3, '2026-02-15', '2026-03-01', null, null, 3);

INSERT INTO lisalasku VALUES (4, '2026-03-05', '2026-03-20', null, 3, 3);
