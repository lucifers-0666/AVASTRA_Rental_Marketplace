-- Prevent a space owner from booking their own listing at the database layer.
-- Apply this after db/schema.sql on an existing local database.

-- Correct the existing erroneous seed booking: space #2 belongs to Rahul (id 3),
-- so its renter is set to the other seed user, Jay (id 2).
UPDATE bookings b
JOIN spaces s ON s.id = b.space_id
SET b.seeker_id = 2
WHERE b.id = 2 AND b.seeker_id = s.owner_id AND s.owner_id = 3;

DROP TRIGGER IF EXISTS prevent_self_booking_before_insert;
DELIMITER $$
CREATE TRIGGER prevent_self_booking_before_insert
BEFORE INSERT ON bookings
FOR EACH ROW
BEGIN
    IF EXISTS (SELECT 1 FROM spaces WHERE id = NEW.space_id AND owner_id = NEW.seeker_id) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'A space owner cannot book their own listing.';
    END IF;
END$$
DELIMITER ;
