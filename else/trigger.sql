
-- Una Stanza che ospita un Laboratorio Interno non può avere un valore per l’attributo NumeroLetti, poiché non è adibita al Ricovero. !!
CREATE OR REPLACE FUNCTION check_lab()
RETURNS TRIGGER AS $$
BEGIN
    
    IF (SELECT numeroLetti FROM Stanza WHERE reparto = NEW.reparto AND numero = NEW.stanza) IS NOT NULL THEN
        RAISE EXCEPTION 'La stanza % del reparto % non ha numeroLetti NULL', NEW.stanza, NEW.reparto;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;


CREATE TRIGGER check_lab_trigger
BEFORE INSERT OR UPDATE ON LaboratorioInterno
FOR EACH ROW
EXECUTE FUNCTION check_lab();

-- Un membro del Personale Amministrativo deve necessariamente lavorare presso un Reparto. Ciò non vale per il Personale Sanitario, che potrebbe lavorare esclusivamente presso un Pronto Soccorso. !!
CREATE OR REPLACE FUNCTION check_reparto()
RETURNS TRIGGER AS $$
BEGIN
    -- Verifica se il CF del nuovo record abbia con un reparto associato
    IF (SELECT reparto FROM Personale WHERE CF = NEW.CF) IS NULL THEN
        RAISE EXCEPTION 'Il dipendente % non ha un reparto associato', NEW.CF;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;



CREATE TRIGGER check_reparto_trigger
BEFORE INSERT OR UPDATE ON PersonaleAmministrativo
FOR EACH ROW
EXECUTE FUNCTION check_reparto();


-- Il Primario di un certo Reparto deve necessariamente lavorare in tale Reparto. !!
CREATE OR REPLACE FUNCTION check_reparto_primario()
RETURNS TRIGGER AS $$
BEGIN
    -- Verifica se il CF e reparto del nuovo record coincidono con un record nella tabella Personale
    IF NOT EXISTS (SELECT 1 FROM Personale WHERE CF = NEW.CF AND reparto = NEW.reparto) THEN
        RAISE EXCEPTION 'Il primario % non lavora nel reparto associato %', NEW.CF, NEW.reparto;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;



CREATE TRIGGER check_reparto_primario_trigger
BEFORE INSERT OR UPDATE ON Primario
FOR EACH ROW
EXECUTE FUNCTION check_reparto_primario();



-- Il Vice Primario di un certo Reparto deve necessariamente lavorare in tale Reparto. !!
CREATE OR REPLACE FUNCTION check_reparto_vice_primario()
RETURNS TRIGGER AS $$
BEGIN
    -- Verifica se il CF e reparto del nuovo record coincidono con un record nella tabella Personale
    IF NOT EXISTS (SELECT 1 FROM Personale WHERE CF = NEW.CF AND reparto = NEW.reparto) THEN
        RAISE EXCEPTION 'Il vice primario % non lavora nel reparto associato %', NEW.CF, NEW.reparto;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;


CREATE TRIGGER check_reparto_vice_primario_trigger
BEFORE INSERT OR UPDATE ON VicePrimario
FOR EACH ROW
EXECUTE FUNCTION check_reparto_vice_primario();



-- Un Primario non può anche essere Vice Primario, nemmeno per Reparti differenti. !!
CREATE OR REPLACE FUNCTION check_primario()
RETURNS TRIGGER AS $$
BEGIN
    -- Verifica se il CF esiste anche nella tabella VicePrimario
    IF EXISTS (SELECT 1 FROM VicePrimario WHERE CF = NEW.CF) THEN
        RAISE EXCEPTION 'Il primario % non può essere anche un viceprimario', NEW.CF;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;


CREATE TRIGGER check_primario_trigger
BEFORE INSERT OR UPDATE ON Primario
FOR EACH ROW
EXECUTE FUNCTION check_primario();

-- Una Sostituzione deve necessariamente coinvolgere Primario e Vice Primario di uno stesso Reparto. !!
CREATE OR REPLACE FUNCTION check_reparto_sostituzione()
RETURNS TRIGGER AS $$
BEGIN
    -- Verifica se il Primario e il Vice Primario sono dello stesso reparto
    IF (SELECT reparto FROM Primario WHERE CF = NEW.primario) != (SELECT reparto FROM VicePrimario WHERE CF = NEW.viceprimario) THEN
        RAISE EXCEPTION 'Il Primario % e il Vice Primario % non sono dello stesso reparto', NEW.primario, NEW.viceprimario;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER check_reparto_sostituzione_trigger
BEFORE INSERT OR UPDATE ON Sostituzione
FOR EACH ROW
EXECUTE FUNCTION check_reparto_sostituzione();


--ovvero un membro del Personale Sanitario non può iniziare un nuovo turno prima di aver terminato il precedente. 
CREATE OR REPLACE FUNCTION check_turno_in_corso()
RETURNS TRIGGER AS $$
BEGIN
    -- Verifica se esiste un ricovero in corso per il paziente
    IF EXISTS (SELECT 1 
               FROM TurnoPS
               WHERE personale = NEW.personale
                 AND (dataOraFine IS NULL 
                 OR dataOraFine > NEW.dataOraInizio)) THEN
        RAISE EXCEPTION 'Il dipendente % è già in turno il %', NEW.personale, NEW.dataOraInizio;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER check_turno_in_corso_trigger
BEFORE INSERT OR UPDATE ON TurnoPS
FOR EACH ROW
EXECUTE FUNCTION check_turno_in_corso();



--Un Ricovero può avvenire solamente in una Stanza adibita a tale attività, ovvero che abbia un contatore per il Numero di Letti e che non ospiti un Laboratorio Interno. !!
CREATE OR REPLACE FUNCTION check_letti()
RETURNS TRIGGER AS $$
BEGIN
    -- Verifica se la stanza ha un numeroLetti diverso da NULL
    IF (SELECT numeroLetti FROM Stanza WHERE reparto = NEW.reparto AND numero = NEW.stanza) IS NULL THEN
        RAISE EXCEPTION 'La stanza % del reparto % ha numeroLetti NULL', NEW.stanza, NEW.reparto;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;


CREATE TRIGGER check_letti_trigger
BEFORE INSERT OR UPDATE ON Ricovero
FOR EACH ROW
EXECUTE FUNCTION check_letti();

--Un Ricovero può avvenire in una certa Stanza solamente se il numero di Ricoveri attualmente in corso in tale Stanza è inferiore al suo Numero di Letti. !!
CREATE OR REPLACE FUNCTION check_letti_stanza()
RETURNS TRIGGER AS $$
DECLARE
    numero_ricoveri INTEGER;
    numero_letti INTEGER;
BEGIN
    -- Conta il numero di ricoveri attualmente in corso nella stanza
    SELECT COUNT(*)
    INTO numero_ricoveri
    FROM Ricovero
    WHERE reparto = NEW.reparto AND stanza = NEW.stanza AND DataFine IS NULL;

    -- Ottieni il numero di letti nella stanza
    SELECT numeroLetti
    INTO numero_letti
    FROM Stanza
    WHERE reparto = NEW.reparto AND numero = NEW.stanza;

    -- Verifica se il numero di ricoveri attualmente in corso è maggiore o uguale al numero di letti
    IF numero_ricoveri >= numero_letti THEN
        RAISE EXCEPTION 'La stanza % del reparto % ha già tutti i letti occupati.', NEW.stanza, NEW.reparto;
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;


CREATE TRIGGER check_letti_stanza_trigger
BEFORE INSERT OR UPDATE ON Ricovero
FOR EACH ROW
EXECUTE FUNCTION check_letti_stanza();


-- Un Paziente non può essere nuovamente ricoverato se è già in corso un suo Ricovero. !!
CREATE OR REPLACE FUNCTION check_ricovero_in_corso()
RETURNS TRIGGER AS $$
BEGIN
    -- Verifica se esiste un ricovero in corso per il paziente
    IF EXISTS (SELECT 1 
               FROM Ricovero
               WHERE paziente = NEW.paziente
                 AND (DataFine IS NULL 
                 OR DataFine > NEW.DataInizio)) THEN
        RAISE EXCEPTION 'Il paziente % è già ricoverato.', NEW.paziente;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER check_ricovero_in_corso_trigger
BEFORE INSERT OR UPDATE ON Ricovero
FOR EACH ROW
EXECUTE FUNCTION check_ricovero_in_corso();


-- Una Prenotazione il cui Esame appare negli Esami Specialistici deve necessariamente avere impostato un valore valido per il Medico Prescrittore. 
CREATE OR REPLACE FUNCTION check_medico_prescrittore()
RETURNS TRIGGER AS $$
BEGIN
    -- Verifica se l'esame è uno specialistico
    IF EXISTS (SELECT 1 FROM EsameSpecialistico WHERE codice = NEW.esame) THEN
        -- Verifica se è stato impostato un medico prescrittore
        IF NEW.Prescrittore IS NULL THEN
            RAISE EXCEPTION 'Devi specificare un medico prescrittore per un esame specialistico. %', NEW.paziente;
        END IF;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;


CREATE TRIGGER check_medico_prescrittore_trigger
BEFORE INSERT OR UPDATE ON Prenotazione
FOR EACH ROW
EXECUTE FUNCTION check_medico_prescrittore();


CREATE OR REPLACE FUNCTION check_and_delete_richiesta()
RETURNS TRIGGER AS $$
BEGIN
    -- Delete from RichiestaPrenotazione where there's a match on Paziente and Esame
    DELETE FROM RichiestaPrenotazione
    WHERE Paziente = NEW.Paziente
      AND Esame = NEW.Esame;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;


CREATE TRIGGER trigger_check_and_delete
AFTER INSERT OR UPDATE ON Prenotazione
FOR EACH ROW
EXECUTE FUNCTION check_and_delete_richiesta();