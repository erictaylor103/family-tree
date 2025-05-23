-- Table: locations
CREATE TABLE locations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    location_name VARCHAR(255) NOT NULL,
    location_latitude FLOAT,
    location_longitude FLOAT
);

-- Table: photos
CREATE TABLE photos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    person_id INT NOT NULL,
    photo_url TEXT NOT NULL,
    photo_caption VARCHAR(255),
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Table: persons
CREATE TABLE persons (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    gender ENUM('male', 'female', 'other') NOT NULL,
    birth_date DATE,
    death_date DATE,
    is_alive BOOLEAN DEFAULT TRUE,
    biography TEXT,
    father_id INT,
    mother_id INT,
    location_id INT,
    main_photo_id INT,

    FOREIGN KEY (father_id) REFERENCES persons(id),
    FOREIGN KEY (mother_id) REFERENCES persons(id),
    FOREIGN KEY (location_id) REFERENCES locations(id),
    FOREIGN KEY (main_photo_id) REFERENCES photos(id)
);

-- Add foreign key to photos table after persons table is created
ALTER TABLE photos
ADD FOREIGN KEY (person_id) REFERENCES persons(id);

-- Table: marriages
CREATE TABLE marriages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    spouse1_id INT NOT NULL,
    spouse2_id INT NOT NULL,
    marriage_date DATE,
    divorce_date DATE,
    end_reason ENUM('divorce', 'death', 'unknown'),
    relationship_type ENUM('marriage', 'civil_partnership', 'common_law', 'other') DEFAULT 'marriage',
    location_id INT,

    FOREIGN KEY (spouse1_id) REFERENCES persons(id),
    FOREIGN KEY (spouse2_id) REFERENCES persons(id),
    FOREIGN KEY (location_id) REFERENCES locations(id)
);