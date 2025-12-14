-- 1. Create Roles
INSERT INTO Roles (role_name) VALUES ('Admin'), ('Faculty'), ('Student');

-- 2. Create Users (passwords would normally be hashed, these are just placeholders)
INSERT INTO Users (username, password_hash, role_id) VALUES 
('admin01', 'hash_secret_123', 1),
('jdoe', 'hash_secret_456', 2),
('mreyes', 'hash_secret_789', 2);

-- 3. Create Departments
INSERT INTO Departments (dept_name, dept_code) VALUES 
('Computer Science', 'CS'),
('Engineering', 'ENG'),
('Mathematics', 'MATH');

-- 4. Create Faculty Profiles (Linking Users and Depts)
-- John Doe is in CS, Maria Reyes is in Engineering
INSERT INTO Faculty_Profiles (user_id, dept_id, first_name, last_name, email, phone_number, designation, hire_date) VALUES 
(2, 1, 'John', 'Doe', 'jdoe@university.edu', '0917-123-4567', 'Associate Professor', '2018-06-01'),
(3, 2, 'Maria', 'Reyes', 'mreyes@university.edu', '0918-987-6543', 'Department Head', '2015-08-15');

-- 5. Add Education Background
INSERT INTO Education (faculty_id, degree_name, institution, year_graduated) VALUES 
(1, 'BS Computer Science', 'Univ. of the Philippines', 2010),
(1, 'MS Computer Science', 'Ateneo de Manila', 2014),
(2, 'PhD Civil Engineering', 'De La Salle University', 2015);

-- 6. Add Publications
INSERT INTO Publications (faculty_id, title, publication_type, published_date) VALUES 
(1, 'Algorithms for Graph Traversal in C++', 'Journal', '2023-01-15'),
(2, 'Sustainable Concrete Mixes', 'Conference', '2024-03-10');

-- 7. Add Courses
INSERT INTO Courses (course_code, course_name, units) VALUES 
('CS101', 'Intro to Computing', 3),
('CS102', 'Data Structures', 3),
('ENG201', 'Statics of Rigid Bodies', 3);

-- 8. Assign Teaching Load
-- John Doe teaches CS101 and CS102
-- Maria Reyes teaches ENG201
INSERT INTO Teaching_Load (faculty_id, course_id, semester, section_name, schedule_time) VALUES 
(1, 1, '1st Sem 2025', 'CS-1A', 'MWF 9:00-10:00'),
(1, 2, '1st Sem 2025', 'CS-2A', 'TTH 13:00-14:30'),
(2, 3, '1st Sem 2025', 'CE-2A', 'MWF 10:00-11:00');