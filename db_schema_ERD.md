+-------------+        +--------------+         +------------------------+
|  persons    |        |  marriages   |         |       locations        |
+-------------+        +--------------+         +------------------------+
| id (PK)     |<-----+ | id (PK)      |      +->| id (PK)                |
| first_name  |      | | spouse1_id   |------+   | location_name          |
| last_name   |      | | spouse2_id   |------+   | location_latitude      |
| gender      |      | | marriage_date|          | location_longitude     |
| birth_date  |      | | divorce_date |          +------------------------+
| death_date  |      | | end_reason   |
| father_id FK|      | | location_id FK |
| mother_id FK|      +--------------+
| location_id FK |
| main_photo_id FK|
+-------------+

         ^
         |
         |                             +----------------------+
         |                             |        photos        |
         |                             +----------------------+
         +----------------------------<| id (PK)              |
                                       | person_id FK         |
                                       | photo_url            |
                                       | photo_caption        |
                                       | uploaded_at          |
                                       +----------------------+
