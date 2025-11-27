-- ======================================================
-- tables
-- ======================================================

CREATE TABLE settore (
  nome_settore VARCHAR(50) PRIMARY KEY,
  num_iscritti INT DEFAULT 0 CHECK (num_iscritti >= 0),
  email_responsabile VARCHAR(100)
);

CREATE TABLE iscritto (
  email VARCHAR(100) PRIMARY KEY,
  password VARCHAR(50) NOT NULL,
  nome VARCHAR(50) NOT NULL,
  cognome VARCHAR(50) NOT NULL,
  data_nascita DATE NOT NULL,
  foto VARCHAR(255),
  data_inizio DATE,
  nome_settore VARCHAR(50),
  nome_ruolo VARCHAR(30) NOT NULL,
  FOREIGN KEY (nome_settore) REFERENCES settore(nome_settore)
    ON DELETE SET NULL,
  CHECK (nome_ruolo IN ('responsabile','docente','allievo','tecnico'))
);

ALTER TABLE settore
  ADD CONSTRAINT fk_resp FOREIGN KEY (email_responsabile)
  REFERENCES iscritto(email)
  ON DELETE SET NULL;

CREATE TABLE sala (
  nome_sala VARCHAR(50),
  nome_settore VARCHAR(50),
  capienza INT NOT NULL CHECK (capienza > 0),
  PRIMARY KEY (nome_sala, nome_settore),
  FOREIGN KEY (nome_settore) REFERENCES settore(nome_settore)
);

CREATE TABLE dotazione (
  nome_dotazione VARCHAR(100),
  nome_sala VARCHAR(50),
  nome_settore VARCHAR(50),
  PRIMARY KEY (nome_dotazione, nome_sala, nome_settore),
  FOREIGN KEY (nome_sala, nome_settore) REFERENCES sala(nome_sala, nome_settore)
);

CREATE TABLE prenotazione (
  id INT AUTO_INCREMENT PRIMARY KEY,
  data_ora_inizio DATETIME NOT NULL,
  durata INT NOT NULL CHECK (durata > 0),
  attivita VARCHAR(100),
  num_iscritti INT,
  criterio VARCHAR(50),
  nome_settore VARCHAR(50),
  nome_sala VARCHAR(50),
  email_responsabile VARCHAR(100),
  FOREIGN KEY (nome_sala, nome_settore) REFERENCES sala(nome_sala, nome_settore),
  FOREIGN KEY (email_responsabile) REFERENCES iscritto(email)
);

CREATE TABLE invito (
  email_iscritto VARCHAR(100),
  id_prenotazione INT,
  data_ora_risposta DATETIME,
  risposta ENUM('si','no'),
  motivazione VARCHAR(255),
  PRIMARY KEY (email_iscritto, id_prenotazione),
  FOREIGN KEY (email_iscritto) REFERENCES iscritto(email),
  FOREIGN KEY (id_prenotazione) REFERENCES prenotazione(id),
  CHECK ((risposta = 'no' AND motivazione IS NOT NULL) OR risposta = 'si')
);



-- ======================================================
-- POPOLAMENTO DI ESEMPIO
-- ======================================================

INSERT INTO settore (nome_settore,num_iscritti) VALUES 
('Musica',5),('Teatro',4),('Danza',3);

INSERT INTO iscritto VALUES
('supermario@bbldrizzy.it','12345678','Mario','Balotelli','1982-02-10',NULL,'2015-01-11','Musica','responsabile'),
('luca@bbldrizzy.it','12345678','Luca','Maxim','1990-05-03',NULL,NULL,'Musica','docente'),
('elvirap@bbldrizzy.it','password','Elvira','Pajetta','1963-11-11',NULL,'2017-03-01','Teatro','responsabile'),
('elia@bbldrizzy.it','password','Elia','Gheri','1992-10-01',NULL,'2019-01-15','Danza','responsabile'),
('carlotta.peda@bbldrizzy.it','password','Carlotta','Peda','2003-07-09',NULL,NULL,'Danza','allievo'),
('aniaj@bbldrizzy.it','password','Anna','Jozefowicz','2003-09-30',NULL,NULL,'Danza','allievo'),
('agaczarnucha@bbldrizzy.it','password','Agnieszka','Kowalczyk','2002-11-11',NULL,NULL,'Danza','tecnico');

INSERT INTO sala VALUES 
('Sala1','Musica',10),('Sala2','Teatro',8),('Sala3','Danza',12);

INSERT INTO dotazione VALUES
('Batteria','Sala1','Musica'),('Chitarra','Sala1','Musica'),
('Specchi','Sala3','Danza'),('Palcoscenico','Sala2','Teatro');

INSERT INTO prenotazione (data_ora_inizio,durata,attivita,num_iscritti,criterio,nome_settore,nome_sala,email_responsabile)
VALUES
('2025-10-24 10:00:00',2,'Prova musicale',5,'tutti','Musica','Sala1','supermario@bbldrizzy.it'),
('2025-10-25 15:00:00',3,'Prova teatrale',4,'settore','Teatro','Sala2','elvirap@bbldrizzy.it'),
('2025-10-26 18:00:00',2,'Lezione di danza',6,'tutti','Danza','Sala3','elia@bbldrizzy.it');

INSERT INTO invito VALUES
('luca@bbldrizzy.it',1,'2025-10-23 09:00:00','si',NULL),
('carlotta.peda@bbldrizzy.it',3,'2025-10-24 12:00:00','si',NULL),
('aniaj@bbldrizzy.it',1,'2025-10-23 09:15:00','si',NULL),
('agaczarnucha@bbldrizzy.it',2,'2025-10-24 10:30:00','si',NULL);

-- ======================================================
-- INTERROGAZIONI PRINCIPALI
-- ======================================================

-- Numero partecipanti e verifica capienza
SELECT P.id,
       COUNT(DISTINCT I.email_iscritto) AS partecipanti,
       S.capienza,
       CASE WHEN COUNT(DISTINCT I.email_iscritto) > S.capienza THEN 'Superata'
            ELSE 'OK' END AS Stato
FROM PRENOTAZIONE P
JOIN SALA S ON P.nome_sala = S.nome_sala AND P.nome_settore = S.nome_settore
LEFT JOIN INVITO I ON P.id = I.id_prenotazione AND I.risposta='si'
GROUP BY P.id, S.capienza;

-- Numero prenotazioni per giorno e sala
SELECT DATE(P.data_ora_inizio) AS giorno, 
       P.nome_sala, 
       COUNT(*) AS num_prenotazioni
FROM PRENOTAZIONE P
GROUP BY giorno, P.nome_sala
ORDER BY giorno;

-- Prenotazioni sovrapposte nella stessa sala
SELECT P1.id AS prenotazione_in_conf, P2.id AS conflitto_con, P1.nome_sala
FROM PRENOTAZIONE P1
JOIN PRENOTAZIONE P2 
  ON P1.nome_sala = P2.nome_sala
 AND P1.id <> P2.id
 AND P1.data_ora_inizio < DATE_ADD(P2.data_ora_inizio, INTERVAL P2.durata HOUR)
 AND DATE_ADD(P1.data_ora_inizio, INTERVAL P1.durata HOUR) > P2.data_ora_inizio;

-- Iscritti con prenotazioni sovrapposte accettate
SELECT i1.email_iscritto, i1.id_prenotazione AS pren1, i2.id_prenotazione AS pren2
FROM invito i1
JOIN prenotazione p1 ON i1.id_prenotazione = p1.id
JOIN invito i2 ON i1.email_iscritto = i2.email_iscritto
JOIN prenotazione p2 ON i2.id_prenotazione = p2.id
WHERE i1.risposta='si' AND i2.risposta='si'
  AND i1.id_prenotazione < i2.id_prenotazione
  AND p1.data_ora_inizio < DATE_ADD(p2.data_ora_inizio, INTERVAL p2.durata HOUR)
  AND DATE_ADD(p1.data_ora_inizio, INTERVAL p1.durata HOUR) > p2.data_ora_inizio;

-- Prenotazioni con più partecipanti degli iscritti del settore
SELECT P.id, COUNT(I.email_iscritto) AS partecipanti, S.num_iscritti
FROM PRENOTAZIONE P
JOIN INVITO I ON P.id = I.id_prenotazione AND I.risposta='si'
JOIN SETTORE S ON P.nome_settore = S.nome_settore
GROUP BY P.id, S.num_iscritti
HAVING COUNT(I.email_iscritto) > S.num_iscritti;

-- Calcolo anni di servizio dei responsabili
SELECT 
  email,
  nome,
  nome_ruolo,
  data_inizio,
  CASE 
    WHEN nome_ruolo = 'responsabile' AND data_inizio IS NOT NULL
    THEN TIMESTAMPDIFF(YEAR, data_inizio, CURDATE())
    ELSE NULL
  END AS anni_servizio
FROM iscritto;
