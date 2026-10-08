-- Personal Tutor Database

CREATE TABLE Department (
DepartmentID int(10) primary key auto_increment,
DepartmentName varchar(75) not null,
Building varchar(75) not null
);

CREATE TABLE Course (
CourseID int(10) primary key auto_increment,
DepartmentID int(10) not null,
CourseName varchar(75) not null,
CourseLevel enum('Foundation', 'Bachelor’s', 'Integrated Master’s', 'Master’s', 'PGCert/PGDip', 'Doctorate', 'PGCE', 'Conversion', 'Fastrack') not null,
foreign key (DepartmentID) references Department(DepartmentID)
		on delete cascade
        on update cascade
);

CREATE TABLE Student (
StudentID int(10) primary key auto_increment,
CourseID int(10) not null,
Forename varchar(50) not null,
Surname varchar(50) not null,
StudentEmail varchar(100) not null,
DateOfBirth date not null,
YearOfStudy enum('1st', '2nd', '3rd', '4th', '5th'),
foreign key (CourseID) references Course(CourseID)
		on delete cascade
        on update cascade
);

CREATE TABLE Tutor (
TutorID int(10) primary key auto_increment,
DepartmentID int(10) not null,
TutorName varchar(100) not null,
StaffEmail varchar(100) not null,
foreign key (DepartmentID) references Department(DepartmentID)
		on delete cascade
        on update cascade
);

CREATE TABLE TutorSchedule (
ScheduleID int(10) primary key auto_increment,
TutorID int(10) not null,
MicrosoftCalendarURL varchar(500) not null,
DaysOnCampus set('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday') null,
foreign key (TutorID) references Tutor(TutorID)
		on delete cascade
        on update cascade
);

CREATE TABLE GroupAllocation (
GroupID int(10) primary key auto_increment,
TutorID int(10) not null,
StudentIDs varchar(25) not null,
foreign key (TutorID) references Tutor(TutorID)
		on delete cascade
        on update cascade
);

CREATE TABLE Allocation (
AllocationID int(10) primary key auto_increment,
TutorID int(10) not null,
StudentID int(10) not null,
GroupID int(10) not null,
foreign key (TutorID) references Tutor(TutorID)
		on delete cascade
        on update cascade,
foreign key (StudentID) references Student(StudentID)
		on delete cascade
        on update cascade,
foreign key (GroupID) references GroupAllocation(GroupID)
		on delete cascade
        on update cascade
);

CREATE TABLE ScheduledMeeting (
MeetingID int(10) primary key auto_increment,
AllocationID int(10),
GroupID int(10),
MeetingType enum('group', 'individual') not null,
ScheduledDateTime datetime not null,
InPerson boolean not null,
Required boolean not null,
foreign key (AllocationID) references Allocation(AllocationID)
		on delete cascade
        on update cascade,
foreign key (GroupID) references GroupAllocation(GroupID)
		on delete cascade
        on update cascade
);

CREATE TABLE MeetingLog (
LogID int(10) primary key auto_increment,
MeetingID int(10) not null,
MeetingStatus enum('scheduled', 'confirmed', 'completed', 'rescheduled', 'cancelled') not null,
RescheduledDateTime datetime null,
DurationMinutes smallint(5) not null,
MeetingTopic enum('academic progress', 'wellbeing check-in', 'personal circumstances', 'induction/transition', 'professional development', 'open discussion'),
MeetingNotes tinytext null,
ReferralMade boolean not null,
foreign key (MeetingID) references ScheduledMeeting(MeetingID)
		on delete cascade
        on update cascade
);

CREATE TABLE SupportService (
ServiceID int(10) primary key auto_increment,
ServiceName varchar(100) not null,
ServiceDescription tinytext not null,
ServiceEmail varchar(100) not null,
OnCampus boolean not null
);

CREATE TABLE Referral (
ReferralID int(10) primary key auto_increment,
LogID int(10) not null,
ServiceID int(10) not null,
ReferralType enum('academic', 'pastoral', 'employability', 'financial', 'safeguarding'),
ReferralReason tinytext not null,
ReferralStatus enum('submitted', 'awaiting student action', 'in progress', 'resolved', 'escalated', 'no further action'),
foreign key (LogID) references MeetingLog(LogID)
		on delete cascade
        on update cascade,
foreign key (ServiceID) references SupportService(ServiceID)
		on delete cascade
        on update cascade
);

DELIMITER $$

CREATE TRIGGER scheduledmeeting_bi
BEFORE INSERT ON ScheduledMeeting
FOR EACH ROW
BEGIN
    -- Rule 1: Exactly one of AllocationID or GroupID must be filled
    IF (NEW.AllocationID IS NULL AND NEW.GroupID IS NULL)
       OR (NEW.AllocationID IS NOT NULL AND NEW.GroupID IS NOT NULL) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Exactly one of AllocationID or GroupID must be filled';
    END IF;

    -- Rule 2: MeetingType must match the filled column
    IF NEW.MeetingType = 'group' AND NEW.GroupID IS NULL THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Group meetings must have GroupID filled';
    END IF;

    IF NEW.MeetingType = 'individual' AND NEW.AllocationID IS NULL THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Individual meetings must have AllocationID filled';
    END IF;
END$$

DELIMITER ;

INSERT INTO Department (DepartmentID, DepartmentName, Building)
VALUES
	(1, 'Computer Science', 'Technology Hub'),
    (2, 'Eduaction', 'Teaching Hub');

INSERT INTO Course (CourseID, DepartmentID, CourseName, CourseLevel)
VALUES
	(1, 1, 'Computing', 'Conversion'),
    (2, 1, 'Cyber Security', 'Bachelor’s'),
    (3, 1, 'Software Engineering', 'Integrated Master’s'),
    (4, 1, 'Data Science', 'Master’s'),
    (5, 1, 'Artificial Intelligence', 'Doctorate'),
    (6, 1, 'Software Development', 'Fastrack'),
    (7, 2, 'Primary Education', 'Bachelor’s'),
    (8, 2, 'Secondary Education', 'Bachelor’s'),
    (9, 2, 'Primary', 'PGCE'),
    (10, 2, 'Secondary', 'PGCE');

INSERT INTO Student (StudentID, CourseID, Forename, Surname, StudentEmail, DateOfBirth, YearOfStudy)
VALUES
	(1, 1, 'Amelia', 'Grant-Hughes', 'amelia.granthughes@edgehill.ac.uk', '1994-04-12', '1st'),
    (2, 1, 'Jordan', 'Patel', 'jordan.patel@edgehill.ac.uk', '1992-09-28', '1st'),
    (3, 1, 'Sophie', 'McAllister', 'sophie.mcallister@edgehill.ac.uk', '1996-01-03', '1st'),
    (4, 1, 'Daniel', 'Osei-Mensah', 'daniel.oseimensah@edgehill.ac.uk', '1990-07-17', '1st'),
    (5, 1, 'Rebecca', 'Turner-Cole', 'rebecca.turnercole@edgehill.ac.uk', '1998-11-22', '1st'),
    (6, 2, 'Lewis', 'Carter', 'lewis.carter@edgehill.ac.uk', '2005-05-14', '3rd'),
    (7, 2, 'Aisha-Mae', 'Rahman', 'aishamae.rahman@edgehill.ac.uk', '2004-10-05', '3rd'),
    (8, 2, 'Benji', 'Thompson-Reed', 'benji.thompsonreed@edgehill.ac.uk', '2005-02-27', '3rd'),
    (9, 2, 'Chloe', 'Martinez', 'chloe.martinez@edgehill.ac.uk', '2003-12-01', '3rd'),
    (10, 2, 'Oliver', 'McBride', 'oliver.mcbride@edgehill.ac.uk', '2004-03-19', '3rd'),
    (11, 3, 'Ethan', 'Rowlands', 'ethan.rowlands@edgehill.ac.uk', '2004-06-06', '4th'),
    (12, 3, 'Priya', 'Shah', 'priya.shah@edgehill.ac.uk', '2004-08-30', '4th'),
    (13, 3, 'Marcus', 'O’Donnell', 'marcus.odonnell@edgehill.ac.uk', '2003-01-11', '4th'),
    (14, 3, 'Hannah', 'Lewis-Barker', 'hannah.lewisbarker@edgehill.ac.uk', '2004-04-25', '4th'),
    (15, 3, 'Samuel', 'Adeyemi', 'samuel.adeyemi@edgehill.ac.uk', '2002-09-02', '4th'),
    (16, 4, 'Charlotte', 'Evans', 'charlotte.evans@edgehill.ac.uk', '1999-02-18', '1st'),
    (17, 4, 'Ibrahim', 'Noor', 'ibrahim.noor@edgehill.ac.uk', '1997-07-07', '1st'),
    (18, 4, 'Emily', 'Fraser-Jones', 'emily.fraserjones@edgehill.ac.uk', '2000-10-29', '1st'),
    (19, 4, 'Thomas-Matthew', 'Gallagher', 'thomasmatthew.gallagher@edgehill.ac.uk', '1996-03-03', '1st'),
    (20, 4, 'Mei-Ling', 'Chen', 'meiling.chen@edgehill.ac.uk', '1998-12-21', '1st'),
    (21, 5, 'Dragon', 'Petrovic', 'dragon.petrovic@edgehill.ac.uk', '1998-09-15', '5th'),
    (22, 5, 'Laura', 'Bennett-King', 'laura.bennettking@edgehill.ac.uk', '1991-04-04', '5th'),
    (23, 5, 'Ahmed', 'Al-Masri', 'ahmed.almasri@edgehill.ac.uk', '1985-06-22', '5th'),
    (24, 5, 'Fiona', 'Clark', 'fiona.clark@edgehill.ac.uk', '1993-11-10', '5th'),
    (25, 5, 'Javier', 'Morales', 'javier.morales@edgehill.ac.uk', '1982-08-01', '5th'),
    (26, 6, 'Katie', 'O’Rourke', 'katie.orourke@edgehill.ac.uk', '2001-03-12', '2nd'),
    (27, 6, 'Nathaniel', 'Brooks', 'nathaniel.brooks@edgehill.ac.uk', '1998-07-30', '2nd'),
    (28, 6, 'Sarah-Jane', 'Whitmore', 'sarahjane.whitmore@edgehill.ac.uk', '1995-05-05', '2nd'),
    (29, 6, 'Connor', 'Davies', 'connor.davies@edgehill.ac.uk', '2003-09-18', '2nd'),
    (30, 6, 'Jade', 'Singh', 'jade.singh@edgehill.ac.uk', '1997-03-24', '2nd'),
    (31, 7, 'Molly', 'Richardson', 'molly.richardson@edgehill.ac.uk', '2005-06-09', '3rd'),
    (32, 7, 'Jack', 'O’Malley', 'jack.omalley@edgehill.ac.uk', '2003-02-14', '3rd'),
    (33, 7, 'Ella-Rose', 'Chapman', 'ellarose.chapman@edgehill.ac.uk', '2004-09-27', '3rd'),
    (34, 7, 'Harry', 'Wilson', 'harry.wilson@edgehill.ac.uk', '2005-03-12', '3rd'),
    (35, 7, 'Zoe', 'Ahmed', 'zoe.ahmend@edgehill.ac.uk', '2004-08-20', '3rd'),
    (36, 8, 'Callum', 'Fraser', 'callum.fraser@edgehill.ac.uk', '2005-11-11', '2nd'),
    (37, 8, 'Ruby', 'Thompson', 'ruby.thompson@edgehill.ac.uk', '2006-04-02', '2nd'),
    (38, 8, 'Joshua-James', 'King', 'joshuajames.king@edgehill.ac.uk', '2005-01-16', '2nd'),
    (39, 8, 'Mia', 'Sullivan-Price', 'mia.sullivanprice@edgehill.ac.uk', '2004-07-23', '2nd'),
    (40, 8, 'Dylan', 'Harper', 'dylan.harper@edgehill.ac.uk', '2005-10-08', '2nd'),
    (41, 9, 'Grace', 'Holloway', 'grace.holloway@edgehill.ac.uk', '1999-03-19', '1st'),
    (42, 9, 'Matthew', 'Osei', 'matthew.osei@edgehill.ac.uk', '1997-12-12', '1st'),
    (43, 9, 'Chloe-Anne', 'Roberts', 'chloeanne.roberts@edgehill.ac.uk', '2000-05-04', '1st'),
    (44, 9, 'Jonathon', 'Clarke', 'jonathon.clarke@edgehill.ac.uk', '1996-08-28', '1st'),
    (45, 9, 'Amina', 'Yusef', 'amina.yusef@edgehill.ac.uk', '1998-01-10', '1st'),
    (46, 10, 'Patrick', 'Doyle', 'patrick.doyle@edgehill.ac.uk', '1997-09-06', '1st'),
    (47, 10, 'Emily', 'Carter-Wells', 'emily.carterwells@edgehill.ac.uk', '1999-02-15', '1st'),
    (48, 10, 'Liam', 'Stuart', 'liam.stuart@edgehill.ac.uk', '1996-07-01', '1st'),
    (49, 10, 'Sienna', 'Patel', 'sienna.patel@edgehill.ac.uk', '2000-11-30', '1st'),
    (50, 10, 'George', 'McKenna', 'george.mckenna@edgehill.ac.uk', '1998-04-22', '1st');
    
INSERT INTO Tutor (TutorID, DepartmentID, TutorName, StaffEmail)
VALUES
	(1, 1, 'Dr Melissa Harding', 'melissa.harding@edgehill.ac.uk'),
    (2, 1, 'Callum Fraser-Wright', 'callum.fraserwright@edgehill.ac.uk'),
    (3, 1, 'Dr Priyanka Deshmukh', 'priyanka.deshmuk@edgehill.ac.uk'),
    (4, 1, 'Dr Nathaniel Brooks', 'nathaniel.brooks@edgehill.ac.uk'),
    (5, 1, 'Adrian McAllister', 'adrian.mcallister@edgehill.ac.uk'),
    (6, 1, 'Talia Osei-Mensah', 'talia.oseimensah@edgehill.ac.uk'),
    (7, 2, 'Joanne Whitfield', 'joanne.whitfield@edgehill.ac.uk'),
    (8, 2, 'Stephen Clarke-Rowe', 'stephen.clarkerowe@edgehill.ac.uk'),
    (9, 2, 'Amina Patel-Hughes', 'amina.patelhughes@edgehill.ac.uk'),
    (10, 2, 'Dr Robert Jennings', 'robert.jennings@edgehill.ac.uk');

INSERT INTO TutorSchedule (ScheduleID, TutorID, MicrosoftCalendarURL, DaysOnCampus)
VALUES
	(1, 1, 'https://outlook.office.com/calendar/edgehill/melissa.harding', 'Monday,Thursday'),
    (2, 2, 'https://outlook.office.com/calendar/edgehill/callum.fraserwright', 'Tuesday,Friday'),
    (3, 3, 'https://outlook.office.com/calendar/edgehill/priyanka.deshmuk', null),
    (4, 4, 'https://outlook.office.com/calendar/edgehill/nathaniel.brooks', 'Monday,Wednesday'),
    (5, 5, 'https://outlook.office.com/calendar/edgehill/adrian.mcallister', null),
    (6, 6, 'https://outlook.office.com/calendar/edgehill/talia.oseimensah', 'Monday,Friday'),
    (7, 7, 'https://outlook.office.com/calendar/edgehill/joanne.whitfield', 'Tuesday,Thursday'),
    (8, 8, 'https://outlook.office.com/calendar/edgehill/stephen.clarkerowe', null),
    (9, 9, 'https://outlook.office.com/calendar/edgehill/amina.patelhughes', null),
    (10, 10, 'https://outlook.office.com/calendar/edgehill/robert.jennings', 'Wednesday,Friday');

INSERT INTO GroupAllocation (GroupID, TutorID, StudentIDs)
VALUES
	(1, 1, '1, 2, 3, 4, 5'),
    (2, 2, '6, 7, 8, 9, 10'),
    (3, 3, '11, 12, 13, 14, 15'),
    (4, 4, '16, 17, 18, 19, 20'),
    (5, 5, '21, 22, 23, 24, 25'),
    (6, 6, '26, 27, 28, 29, 30'),
    (7, 7, '31, 32, 33, 34, 35'),
    (8, 8, '36, 37, 38, 39, 40'),
    (9, 9, '41, 42, 43, 44, 45'),
    (10, 10, '46, 47, 48, 49, 50');

INSERT INTO Allocation (AllocationID, TutorID, StudentID, GroupID)
VALUES
	(1, 1, 1, 1),
    (2, 1, 2, 1),
    (3, 1, 3, 1),
    (4, 1, 4, 1),
    (5, 1, 5, 1),
    (6, 2, 6, 2),
    (7, 2, 7, 2),
    (8, 2, 8, 2),
    (9, 2, 9, 2),
    (10, 2, 10, 2),
    (11, 3, 11, 3),
    (12, 3, 12, 3),
    (13, 3, 13, 3),
    (14, 3, 14, 3),
    (15, 3, 15, 3),
    (16, 4, 16, 4),
    (17, 4, 17, 4),
    (18, 4, 18, 4),
    (19, 4, 19, 4),
    (20, 4, 20, 4),
    (21, 5, 21, 5),
    (22, 5, 22, 5),
    (23, 5, 23, 5),
    (24, 5, 24, 5),
    (25, 5, 25, 5),
    (26, 6, 26, 6),
    (27, 6, 27, 6),
    (28, 6, 28, 6),
    (29, 6, 29, 6),
    (30, 6, 30, 6),
    (31, 7, 31, 7),
    (32, 7, 32, 7),
    (33, 7, 33, 7),
    (34, 7, 34, 7),
    (35, 7, 35, 7),
    (36, 8, 36, 8),
    (37, 8, 37, 8),
    (38, 8, 38, 8),
    (39, 8, 39, 8),
    (40, 8, 40, 8),
    (41, 9, 41, 9),
    (42, 9, 42, 9),
    (43, 9, 43, 9),
    (44, 9, 44, 9),
    (45, 9, 45, 9),
    (46, 10, 46, 10),
    (47, 10, 47, 10),
    (48, 10, 48, 10),
    (49, 10, 49, 10),
    (50, 10, 50, 10);

INSERT INTO ScheduledMeeting (MeetingID, AllocationID, GroupID, MeetingType, ScheduledDateTime, InPerson, Required)
VALUES
	(1, null, 1, 'group', '2026-01-19 10:00:00', 1, 1),
    (2, 1, null, 'individual', '2026-01-22 10:00:00', 1, 1),
    (3, 2, null, 'individual', '2026-01-22 10:30:00', 1, 1),
    (4, 3, null, 'individual', '2026-01-22 11:00:00', 1, 1),
    (5, 4, null, 'individual', '2026-01-22 11:30:00', 1, 1),
    (6, 5, null, 'individual', '2026-01-22 12:00:00', 1, 1),
    (7, null, 2, 'group', '2026-01-20 10:00:00', 1, 1),
    (8, 6, null, 'individual', '2026-01-23 10:00:00', 1, 1),
    (9, 7, null, 'individual', '2026-01-23 10:30:00', 1, 1),
    (10, 8, null, 'individual', '2026-01-23 11:00:00', 1, 1),
    (11, 9, null, 'individual', '2026-01-23 11:30:00', 1, 1),
    (12, 10, null, 'individual', '2026-01-23 12:00:00', 1, 1),
    (13, null, 3, 'group', '2026-01-19 10:00:00', 0, 1),
    (14, 11, null, 'individual', '2026-01-19 11:00:00', 0, 1),
    (15, 12, null, 'individual', '2026-01-19 11:30:00', 0, 1),
    (16, 13, null, 'individual', '2026-01-19 12:00:00', 0, 1),
    (17, 14, null, 'individual', '2026-01-19 12:30:00', 0, 1),
    (18, 15, null, 'individual', '2026-01-19 13:00:00', 0, 1),
    (19, null, 4, 'group', '2026-01-19 13:00:00', 1, 1),
    (20, 16, null, 'individual', '2026-01-21 13:00:00', 1, 1),
    (21, 17, null, 'individual', '2026-01-21 13:30:00', 1, 1),
    (22, 18, null, 'individual', '2026-01-21 14:00:00', 1, 1),
    (23, 19, null, 'individual', '2026-01-21 14:30:00', 1, 1),
    (24, 20, null, 'individual', '2026-01-21 15:00:00', 1, 1),
    (25, null, 5, 'group', '2026-01-22 13:00:00', 0, 1),
    (26, 21, null, 'individual', '2026-01-22 14:00:00', 0, 1),
    (27, 22, null, 'individual', '2026-01-22 14:30:00', 0, 1),
    (28, 23, null, 'individual', '2026-01-22 15:00:00', 0, 1),
    (29, 24, null, 'individual', '2026-01-22 15:30:00', 0, 1),
    (30, 25, null, 'individual', '2026-01-22 16:00:00', 0, 1),
    (31, null, 6, 'group', '2026-01-19 13:00:00', 1, 1),
    (32, 26, null, 'individual', '2026-01-23 10:00:00', 1, 1),
    (33, 27, null, 'individual', '2026-01-23 10:30:00', 1, 1),
    (34, 28, null, 'individual', '2026-01-23 11:00:00', 1, 1),
    (35, 29, null, 'individual', '2026-01-23 11:30:00', 1, 1),
    (36, 30, null, 'individual', '2026-01-23 12:00:00', 1, 1),
    (37, null, 7, 'group', '2026-01-20 13:00:00', 1, 1),
    (38, 31, null, 'individual', '2026-01-22 13:00:00', 1, 1),
    (39, 32, null, 'individual', '2026-01-22 13:30:00', 1, 1),
    (40, 33, null, 'individual', '2026-01-22 14:00:00', 1, 1),
    (41, 34, null, 'individual', '2026-01-22 14:30:00', 1, 1),
    (42, 35, null, 'individual', '2026-01-22 15:00:00', 1, 1),
    (43, null, 8, 'group', '2026-01-21 10:00:00', 0, 1),
    (44, 36, null, 'individual', '2026-01-21 11:00:00', 0, 1),
    (45, 37, null, 'individual', '2026-01-21 11:30:00', 0, 1),
    (46, 38, null, 'individual', '2026-01-21 11:00:00', 0, 1),
    (47, 39, null, 'individual', '2026-01-21 12:00:00', 0, 1),
    (48, 40, null, 'individual', '2026-01-21 12:30:00', 0, 1),
    (49, null, 9, 'group', '2026-01-23 13:00:00', 0, 1),
    (50, 41, null, 'individual', '2026-01-23 14:00:00', 0, 1),
    (51, 42, null, 'individual', '2026-01-23 14:30:00', 0, 1),
    (52, 43, null, 'individual', '2026-01-23 15:00:00', 0, 1),
    (53, 44, null, 'individual', '2026-01-23 15:30:00', 0, 1),
    (54, 45, null, 'individual', '2026-01-23 16:00:00', 0, 1),
    (55, null, 10, 'group', '2026-01-21 10:00:00', 1, 1),
    (56, 46, null, 'individual', '2026-01-23 13:00:00', 1, 1),
    (57, 47, null, 'individual', '2026-01-23 13:30:00', 1, 1),
    (58, 48, null, 'individual', '2026-01-23 14:00:00', 1, 1),
    (59, 49, null, 'individual', '2026-01-23 14:30:00', 1, 1),
    (60, 50, null, 'individual', '2026-01-23 15:00:00', 1, 1);

INSERT INTO MeetingLog (LogID, MeetingID, MeetingStatus, RescheduledDateTime, DurationMinutes, MeetingTopic, MeetingNotes, ReferralMade)
VALUES
-- Meeting 1 
(1, 1, 'scheduled', null, 45, 'induction/transition', 'Meeting scheduled to introduce course expectations.', 0),
(2, 1, 'completed', null, 45, 'induction/transition', 'Introduced course expectations and support services.', 0),

-- Meeting 2 
(3, 2, 'scheduled', null, 30, 'academic progress', 'Initial meeting arranged.', 0),
(4, 2, 'confirmed', null, 30, 'academic progress', 'Student confirmed attendance.', 0),
(5, 2, 'completed', null, 30, 'academic progress', 'Reviewed early engagement and study habits.', 0),

-- Meeting 3 
(6, 3, 'scheduled', null, 30, 'wellbeing check-in', 'Meeting arranged to check student wellbeing.', 0),
(7, 3, 'completed', null, 30, 'wellbeing check-in', 'Student reported positive start to term.', 0),

-- Meeting 4 
(8, 4, 'scheduled', null, 30, 'academic progress', 'Meeting scheduled to review assessment structure.', 0),

-- Meeting 5 
(9, 5, 'completed', null, 30, 'professional development', 'Discussed long-term career goals.', 0),

-- Meeting 6 
(10, 6, 'scheduled', null, 30, 'academic progress', 'Initial meeting arranged.', 0),
(11, 6, 'rescheduled', '2026-01-24 12:00:00', 30, 'academic progress', 'Student requested new time due to timetable clash.', 0),
(12, 6, 'completed', null, 30, 'academic progress', 'Reviewed assignment planning.', 0),

-- Meeting 7 
(13, 7, 'completed', null, 45, 'open discussion', 'Group shared expectations for the module.', 0),

-- Meeting 8 
(14, 8, 'completed', null, 30, 'academic progress', 'Student asked about referencing.', 0),

-- Meeting 9 
(15, 9, 'completed', null, 30, 'personal circumstances', 'Student disclosed temporary family commitments.', 1),

-- Meeting 10 
(16, 10, 'confirmed', null, 30, 'academic progress', 'Student confirmed attendance via email.', 0),
(17, 10, 'completed', null, 30, 'academic progress', 'Reviewed attendance and engagement.', 0),

-- Meeting 11 
(18, 11, 'completed', null, 30, 'wellbeing check-in', 'Student reported mild stress but coping.', 0),

-- Meeting 12 
(19, 12, 'scheduled', null, 30, 'academic progress', 'Meeting arranged to discuss feedback.', 0),
(20, 12, 'completed', null, 30, 'academic progress', 'Discussed feedback from previous work.', 0),

-- Meeting 13 
(21, 13, 'completed', null, 45, 'open discussion', 'Group explored expectations for the term.', 0),

-- Meeting 14 
(22, 14, 'completed', null, 30, 'personal circumstances', 'Student requested flexibility due to travel issues.', 0),

-- Meeting 15 
(23, 15, 'confirmed', null, 30, 'academic progress', 'Student confirmed attendance.', 0),
(24, 15, 'completed', null, 30, 'academic progress', 'Reviewed reading strategies.', 0),

-- Meeting 16 
(25, 16, 'completed', null, 30, 'professional development', 'Discussed placement opportunities.', 0),

-- Meeting 17 
(26, 17, 'completed', null, 30, 'academic progress', 'Reviewed progress on first assignment.', 0),

-- Meeting 18 
(27, 18, 'cancelled', null, 0, 'wellbeing check-in', 'Student cancelled due to illness.', 0),
(28, 18, 'rescheduled', '2026-01-25 10:00:00', 30, 'wellbeing check-in', 'Meeting rescheduled after illness.', 0),
(29, 18, 'completed', null, 30, 'wellbeing check-in', 'Student reported feeling more confident.', 0),

-- Meeting 19 
(30, 19, 'completed', null, 45, 'open discussion', 'Group discussed assessment structure.', 0),

-- Meeting 20 
(31, 20, 'completed', null, 30, 'academic progress', 'Student asked about exam preparation.', 0),

-- Meeting 21 
(32, 21, 'completed', null, 30, 'induction/transition', 'Discussed settling into university routines.', 0),

-- Meeting 22 
(33, 22, 'confirmed', null, 30, 'academic progress', 'Student confirmed attendance.', 0),
(34, 22, 'completed', null, 30, 'academic progress', 'Reviewed attendance and engagement.', 0),

-- Meeting 23 
(35, 23, 'completed', null, 30, 'personal circumstances', 'Student disclosed part-time work pressures.', 0),

-- Meeting 24 
(36, 24, 'scheduled', null, 30, 'academic progress', 'Meeting arranged to review module expectations.', 0),

-- Meeting 25 
(37, 25, 'completed', null, 45, 'open discussion', 'Group discussed project planning.', 0),

-- Meeting 26 
(38, 26, 'completed', null, 30, 'wellbeing check-in', 'Student reported difficulty balancing workload.', 1),

-- Meeting 27 
(39, 27, 'confirmed', null, 30, 'academic progress', 'Student confirmed attendance.', 0),
(40, 27, 'completed', null, 30, 'academic progress', 'Discussed study habits.', 0),

-- Meeting 28 
(41, 28, 'completed', null, 30, 'professional development', 'Explored future career pathways.', 0),

-- Meeting 29 
(42, 29, 'completed', null, 30, 'academic progress', 'Student progressing well.', 0),

-- Meeting 30 
(43, 30, 'scheduled', null, 30, 'induction/transition', 'Meeting arranged to discuss expectations.', 0),
(44, 30, 'cancelled', null, 0, 'induction/transition', 'Student cancelled due to timetable conflict.', 0),

-- Meeting 31 
(45, 31, 'completed', null, 45, 'open discussion', 'Group explored module themes.', 0),

-- Meeting 32 
(46, 32, 'completed', null, 30, 'academic progress', 'Reviewed attendance.', 0),

-- Meeting 33 
(47, 33, 'completed', null, 30, 'wellbeing check-in', 'Student reported feeling overwhelmed.', 1),

-- Meeting 34 
(48, 34, 'confirmed', null, 30, 'academic progress', 'Student confirmed attendance.', 0),
(49, 34, 'completed', null, 30, 'academic progress', 'Reviewed progress.', 0),

-- Meeting 35 
(50, 35, 'completed', null, 30, 'professional development', 'Discussed CV-building opportunities.', 0),

-- Meeting 36 
(51, 36, 'completed', null, 30, 'academic progress', 'Student confident with workload.', 0),

-- Meeting 37 
(52, 37, 'completed', null, 45, 'open discussion', 'Group discussed assessment expectations.', 0),

-- Meeting 38 
(53, 38, 'scheduled', null, 30, 'academic progress', 'Meeting arranged to review assignment brief.', 0),
(54, 38, 'completed', null, 30, 'academic progress', 'Reviewed assignment brief.', 0),

-- Meeting 39 
(55, 39, 'completed', null, 30, 'personal circumstances', 'Student disclosed temporary health concerns.', 1),

-- Meeting 40 
(56, 40, 'completed', null, 30, 'academic progress', 'Discussed reading strategies.', 0),

-- Meeting 41 
(57, 41, 'confirmed', null, 30, 'wellbeing check-in', 'Student confirmed attendance.', 0),
(58, 41, 'completed', null, 30, 'wellbeing check-in', 'Student reported improved motivation.', 0),

-- Meeting 42 
(59, 42, 'completed', null, 30, 'academic progress', 'Student asked about exam preparation.', 0),

-- Meeting 43 
(60, 43, 'completed', null, 45, 'open discussion', 'Group discussed wellbeing resources.', 0),

-- Meeting 44 
(61, 44, 'completed', null, 30, 'wellbeing check-in', 'Student reported feeling overwhelmed.', 1),

-- Meeting 45 
(62, 45, 'cancelled', null, 0, 'personal circumstances', 'Student unable to attend due to home-life pressures.', 1),
(63, 45, 'rescheduled', '2026-01-24 15:00:00', 30, 'personal circumstances', 'Meeting rescheduled.', 1),
(64, 45, 'completed', null, 30, 'personal circumstances', 'Follow-up completed successfully.', 1),

-- Meeting 46 
(65, 46, 'completed', null, 30, 'academic progress', 'Reviewed progress.', 0),

-- Meeting 47 
(66, 47, 'completed', null, 30, 'professional development', 'Discussed future placement options.', 0),

-- Meeting 48 
(67, 48, 'confirmed', null, 30, 'academic progress', 'Student confirmed attendance.', 0),
(68, 48, 'completed', null, 30, 'academic progress', 'Discussed study habits.', 0),

-- Meeting 49 
(69, 49, 'completed', null, 45, 'open discussion', 'Group discussed project planning.', 0),

-- Meeting 50 
(70, 50, 'completed', null, 30, 'academic progress', 'Reviewed assignment expectations.', 0),

-- Meeting 51 
(71, 51, 'scheduled', null, 30, 'academic progress', 'Meeting arranged to review progress.', 0),

-- Meeting 52 
(72, 52, 'completed', null, 30, 'professional development', 'Discussed long-term goals.', 0),

-- Meeting 53 
(73, 53, 'confirmed', null, 30, 'academic progress', 'Student confirmed attendance.', 0),

-- Meeting 54 
(74, 54, 'completed', null, 30, 'wellbeing check-in', 'Student reported improved confidence.', 0),

-- Meeting 55 
(75, 55, 'completed', null, 45, 'open discussion', 'Group engaged well.', 0),

-- Meeting 56 
(76, 56, 'completed', null, 30, 'academic progress', 'Reviewed progress.', 0),

-- Meeting 57 
(77, 57, 'completed', null, 30, 'personal circumstances', 'Student disclosed caring responsibilities.', 1),

-- Meeting 58 
(78, 58, 'confirmed', null, 30, 'academic progress', 'Student confirmed attendance.', 0),

-- Meeting 59 
(79, 59, 'completed', null, 30, 'professional development', 'Explored career pathways.', 0),

-- Meeting 60 
(80, 60, 'cancelled', null, 0, 'academic progress', 'Student cancelled due to timetable conflict.', 0),
(81, 60, 'cancelled', null, 0, 'academic progress', 'Student cancelled again; will rebook later.', 0);

INSERT INTO SupportService (ServiceID, ServiceName, ServiceDescription, ServiceEmail, OnCampus)
VALUES
	(1, 'Academic Skills Hub', 'Helps students develop study skills, referencing, critical writing, and exam preparation through workshops.', 'academic.skills@edgehill.ac.uk', 1),
    (2, 'Digital Learning Centre', 'Provides assistance with VLE navigation, software tools, online submissions, and digital literacy.', 'digital.learning@hotmail.com', 0),
    (3, 'Student Wellbeing & Counselling', 'Offers emotional support, short‑term counselling, and wellbeing workshops for students.', 'wellbeing.counselling@edgehill.ac.uk', 1),
    (4, 'Peer Support & Mentoring Network', 'Connects new students with trained peer mentors to help with transition, confidence, and social integration.', 'peermentor.support@edgehill.ac.uk', 1),
    (5, 'Community Wellbeing Partnership', 'Links to local mental health charities and community support groups for specialist support.', 'community.wellbeing@hotmail.com', 0),
    (6, 'Careers & Employability Hub', 'Supports CV development, interview preparation, job searching, and career planning through workshops and guidance.', 'careers.employability@edgehill.ac.uk', 1),
    (7, 'Placement & Internship Office', 'Helps students secure placements, internships, and work experience opportunities with partner organisations.', 'placement.internship@edgehill.ac.uk', 1),
    (8, 'Graduate Futures Network', 'A regional employability service offering alumni mentoring, networking events, and graduate job fairs.', 'graduate.futures@hotmail.com', 0),
    (9, 'Financial Aid & Bursary Office', 'Supports students applying for bursaries, scholarships, and emergency financial assistance.', 'financial.bursary@edgehill.ac.uk', 1),
    (10, 'Community Debt & Welfare Advice', 'Independent advisors offering support with benefits, debt management, and financial rights.', 'community.debtwelfare@hotmail.com', 0),
    (11, 'Safeguarding & Protection Team', 'Handles safeguarding disclosures, risk assessments, and student safety plans.', 'safeguarding.protection@edgehill.ac.uk', 1),
    (12, 'Campus Security & SafeZone', 'Provides 24/7 safety support, incident reporting, and emergency response on campus.', 'campus.secuirty@edgehill.ac.uk', 1),
    (13, 'Local Authority Safeguarding', 'Works with external agencies to support students at risk of harm or exploitation.', 'local.safeguarding@hotmail.com', 0);

INSERT INTO Referral (ReferralID, LogID, ServiceID, ReferralType, ReferralReason, ReferralStatus)
VALUES
-- Log 15
(1, 15, 3, 'pastoral',
 'Student reported problems at home affecting concentration and wellbeing.',
 'submitted'),

-- Log 47
(2, 47, 3, 'pastoral',
 'Student expressed feeling overwhelmed and requested emotional support.',
 'in progress'),

-- Log 55
(3, 55, 5, 'pastoral',
 'Student disclosed ongoing health issues impacting attendance and engagement.',
 'awaiting student action'),

-- Log 61
(4, 61, 3, 'pastoral',
 'Student reported feeling overwhelmed and was encouraged to access counselling.',
 'submitted'),

-- Log 62
(5, 62, 11, 'safeguarding',
 'Student disclosed significant pressure at home that may affect safety and wellbeing.',
 'in progress'),

-- Log 63
(6, 63, 13, 'safeguarding',
 'Student reported personal problems causing repeated cancellations; external safeguarding referral required.',
 'escalated'),

-- Log 64
(7, 64, 4, 'pastoral',
 'Student discussed personal circumstances requiring additional emotional support.',
 'resolved'),

-- Log 77
(8, 77, 5, 'pastoral',
 'Student disclosed caring responsibilities at home and requested wellbeing support.',
 'submitted');

-- students can see tutor information
SELECT 
	Tutor.TutorName, 
    Tutor.StaffEmail, 
    TutorSchedule.MicrosoftCalendarURL,
    TutorSchedule.DaysOnCampus
FROM Tutor
INNER JOIN TutorSchedule ON Tutor.TutorID = TutorSchedule.TutorID;

-- student can see tutor information
SELECT 
	CONCAT(Student.Forename, '', Student.Surname) AS StudentFullName,
	Tutor.TutorName, 
    Tutor.StaffEmail, 
    TutorSchedule.MicrosoftCalendarURL,
    TutorSchedule.DaysOnCampus
FROM Allocation
INNER JOIN Student ON Allocation.StudentID = Student.StudentID
INNER JOIN Tutor ON Allocation.TutorID = Tutor.TutorID
INNER JOIN TutorSchedule ON Tutor.TutorID = TutorSchedule.TutorID
WHERE Allocation.StudentID = 21;

-- student can see scheduled meetings
-- individual meetings
SELECT 
	CONCAT(s.Forename, '', s.Surname) AS StudentFullName,
    sm.MeetingID, 
    sm.MeetingType,
    sm.ScheduledDateTime,
    sm.InPerson
FROM Allocation AS a
INNER JOIN Student AS s ON s.StudentID = a.StudentID
INNER JOIN ScheduledMeeting AS sm ON sm.AllocationID = a.AllocationID
WHERE a.StudentID = 36

UNION

-- group meetings
SELECT 
	CONCAT(s.Forename, '', s.Surname) AS StudentFullName,
    sm.MeetingID, 
    sm.MeetingType,
    sm.ScheduledDateTime,
    sm.InPerson
FROM Allocation AS a
INNER JOIN Student AS s ON s.StudentID = a.StudentID
INNER JOIN ScheduledMeeting AS sm ON sm.GroupID = a.GroupID
WHERE a.StudentID = 36

ORDER BY ScheduledDateTime;

-- student can see logged meetings
-- individual meetings
SELECT 
	CONCAT(s.Forename, '', s.Surname) AS StudentFullName,
    ml.*
FROM Allocation AS a
JOIN Student AS s on s.StudentID = a.StudentID
JOIN ScheduledMeeting AS sm ON sm.AllocationID = a.AllocationID
JOIN MeetingLog AS ml ON ml.MeetingID = sm.MeetingID
WHERE a.StudentID = 50
    
UNION

-- group meetings
SELECT 
	CONCAT(s.Forename, '', s.Surname) AS StudentFullName,
    ml.*
FROM Allocation AS a
JOIN Student AS s on s.StudentID = a.StudentID
JOIN ScheduledMeeting AS sm ON sm.GroupID = a.GroupID
JOIN MeetingLog AS ml ON ml.MeetingID = sm.MeetingID
WHERE a.StudentID = 50

ORDER BY MeetingID, LogID;

-- student can see referral information
SELECT
	CONCAT(s.Forename, ' ', s.Surname) AS StudentFullName,
    r.ReferralID, 
    r.ReferralType,
    r.ReferralReason,
    r.ReferralStatus,
    ss.ServiceName,
    ss.ServiceEmail
FROM Allocation AS a
JOIN Student AS s ON s.StudentID = a.StudentID
JOIN ScheduledMeeting AS sm ON sm.AllocationID = a.AllocationID 
JOIN MeetingLog AS ml ON ml.MeetingID = sm.MeetingID
JOIN Referral AS r ON r.LogID = ml.LogID
JOIN SupportService AS ss ON ss.ServiceID = r.ServiceID
WHERE a.StudentID = 7

UNION

SELECT
	CONCAT(s.Forename, ' ', s.Surname) AS StudentFullName,
    r.ReferralID, 
    r.ReferralType,
    r.ReferralReason,
    r.ReferralStatus,
    ss.ServiceName,
    ss.ServiceEmail 
FROM Allocation AS a
JOIN Student AS s ON s.StudentID = a.StudentID
JOIN ScheduledMeeting AS sm ON sm.GroupID = a.GroupID
JOIN MeetingLog AS ml ON ml.MeetingID = sm.MeetingID
JOIN Referral AS r ON r.LogID = ml.LogID
JOIN SupportService AS ss ON ss.ServiceID = r.ServiceID
WHERE a.StudentID = 7;

-- tutor can see assigned students
SELECT
    t.TutorName,
    CONCAT(s.Forename, ' ', s.Surname) AS StudentNames,
    s.StudentEmail
FROM Tutor AS t
JOIN Allocation AS a ON a.TutorID = t.TutorID
JOIN Student AS s ON s.StudentID = a.StudentID
WHERE t.TutorID = 9 

UNION

-- tutor can see assigned groups
SELECT
    t.TutorName,
    GROUP_CONCAT(CONCAT(s.Forename, ' ', s.Surname) SEPARATOR ', ') AS StudentNames,
    NULL AS StudentEmail
FROM Tutor AS t
JOIN Allocation AS a ON a.TutorID = t.TutorID
JOIN Student AS s ON s.StudentID = a.StudentID
WHERE t.TutorID = 9
GROUP BY t.TutorName, a.GroupID;

-- tutor can see scheduled meetings
-- Individual meetings
SELECT 
    t.TutorName,
    sm.MeetingID,
    sm.MeetingType,
    sm.ScheduledDateTime,
    sm.InPerson,
    CONCAT(s.Forename, ' ', s.Surname) AS MeetingParticipants
FROM Allocation AS a
JOIN Tutor AS t ON t.TutorID = a.TutorID
JOIN Student AS s ON s.StudentID = a.StudentID
JOIN ScheduledMeeting AS sm ON sm.AllocationID = a.AllocationID
WHERE a.TutorID = 2

UNION

-- Group meetings
SELECT 
    t.TutorName,
    sm.MeetingID,
    sm.MeetingType,
    sm.ScheduledDateTime,
    sm.InPerson,
    GROUP_CONCAT(CONCAT(s.Forename, ' ', s.Surname) SEPARATOR ', ') AS MeetingParticipants
FROM Allocation AS a
JOIN Tutor AS t ON t.TutorID = a.TutorID
JOIN Student AS s ON s.StudentID = a.StudentID
JOIN ScheduledMeeting AS sm ON sm.GroupID = a.GroupID
WHERE a.TutorID = 2
GROUP BY 
    t.TutorName,
    sm.MeetingID,
    sm.MeetingType,
    sm.ScheduledDateTime,
    sm.InPerson

ORDER BY ScheduledDateTime;

-- tutor can schedule meeting
INSERT INTO ScheduledMeeting (MeetingID, AllocationID, GroupID, MeetingType, ScheduledDateTime, InPerson, Required)
VALUES (61, null, 5, 'group', '2026-03-12 12:00:00', 0, 0);

-- tutor can see logged meetings
-- individual meetings
SELECT 
	t.TutorName,
    ml.*,
    CONCAT(s.Forename, '', s.Surname) AS MeetingParticipants
FROM Allocation AS a
JOIN Tutor AS t ON t.TutorID = a.TutorID
JOIN Student AS s on s.StudentID = a.StudentID
JOIN ScheduledMeeting AS sm ON sm.AllocationID = a.AllocationID
JOIN MeetingLog AS ml ON ml.MeetingID = sm.MeetingID
WHERE a.TutorID = 3
    
UNION

-- group meetings
SELECT 
	t.TutorName,
    ml.*,
    GROUP_CONCAT(CONCAT(s.Forename, ' ', s.Surname) SEPARATOR ', ') AS MeetingParticipants
FROM Allocation AS a
JOIN Tutor AS t ON t.TutorID = a.TutorID
JOIN Student AS s on s.StudentID = a.StudentID
JOIN ScheduledMeeting AS sm ON sm.GroupID = a.GroupID
JOIN MeetingLog AS ml ON ml.MeetingID = sm.MeetingID
WHERE a.TutorID = 3

ORDER BY MeetingID, LogID;

-- tutor can log meeting
INSERT INTO MeetingLog (LogID, MeetingID, MeetingStatus, RescheduledDateTime, DurationMinutes, MeetingTopic, MeetingNotes, ReferralMade)
VALUES (82, 61, 'completed', null, 45, 'professional development', 'Group discussed importance of finding work experience', 1);

-- tutor can see referrals made
SELECT
	t.TutorName,
    r.ReferralID, 
    r.ReferralType,
    r.ReferralReason,
    r.ReferralStatus,
    ss.ServiceName,
    ss.ServiceEmail,
    CONCAT(s.Forename, ' ', s.Surname) AS StudentNames
FROM Allocation AS a
JOIN Tutor AS t ON t.TutorID = a.TutorID
JOIN Student AS s ON s.StudentID = a.StudentID
JOIN ScheduledMeeting AS sm ON sm.AllocationID = a.AllocationID 
JOIN MeetingLog AS ml ON ml.MeetingID = sm.MeetingID
JOIN Referral AS r ON r.LogID = ml.LogID
JOIN SupportService AS ss ON ss.ServiceID = r.ServiceID
WHERE a.TutorID = 8

UNION

SELECT
	t.TutorID,
    r.ReferralID, 
    r.ReferralType,
    r.ReferralReason,
    r.ReferralStatus,
    ss.ServiceName,
    ss.ServiceEmail,
    GROUP_CONCAT(CONCAT(s.Forename, ' ', s.Surname) SEPARATOR ', ') AS StudentNames
FROM Allocation AS a
JOIN Tutor AS t ON t.TutorID = a.TutorID
JOIN Student AS s ON s.StudentID = a.StudentID
JOIN ScheduledMeeting AS sm ON sm.GroupID = a.GroupID
JOIN MeetingLog AS ml ON ml.MeetingID = sm.MeetingID
JOIN Referral AS r ON r.LogID = ml.LogID
JOIN SupportService AS ss ON ss.ServiceID = r.ServiceID
WHERE a.TutorID = 8;

-- tutor can make referral
INSERT INTO Referral (ReferralID, LogID, ServiceID, ReferralType, ReferralReason, ReferralStatus)
VALUES (9, 82, 7, 'employability', 'Group all wanted to start work experience', 'in progress');

-- tutor can see students with multiple referrals
SELECT
    x.StudentID,
    CONCAT(s.Forename, ' ', s.Surname) AS StudentFullName,
    s.StudentEmail,
    COUNT(*) AS ReferralCount
FROM (
    -- Referrals from individual meetings
    SELECT a.StudentID, ml.LogID
    FROM Allocation AS a
    JOIN ScheduledMeeting AS sm ON sm.AllocationID = a.AllocationID
    JOIN MeetingLog AS ml ON ml.MeetingID = sm.MeetingID
    JOIN Referral AS r ON r.LogID = ml.LogID
    WHERE a.TutorID = 8

    UNION ALL

    -- Referrals from group meetings
    SELECT a.StudentID, ml.LogID
    FROM Allocation AS a
    JOIN ScheduledMeeting AS sm ON sm.GroupID = a.GroupID
    JOIN MeetingLog AS ml ON ml.MeetingID = sm.MeetingID
    JOIN Referral AS r ON r.LogID = ml.LogID
    WHERE a.TutorID = 8
) AS x
JOIN Student AS s ON s.StudentID = x.StudentID
GROUP BY x.StudentID, StudentFullName, s.StudentEmail
HAVING COUNT(*) > 1
ORDER BY ReferralCount DESC, StudentFullName;

-- tutor can see support service information
SELECT *
FROM SupportService;

-- management can see % of tutors completed required meetings
WITH
-- collect all required meetings and the owning TutorID, de-duplicated
required_meetings AS (
    -- individual meetings
    SELECT DISTINCT a.TutorID, sm.MeetingID
    FROM Allocation a
    JOIN ScheduledMeeting sm ON sm.AllocationID = a.AllocationID
    WHERE sm.Required = 1

    UNION

    -- group meetings
    SELECT DISTINCT a.TutorID, sm.MeetingID
    FROM Allocation a
    JOIN ScheduledMeeting sm ON sm.GroupID = a.GroupID
    WHERE sm.Required = 1
),

-- collapse MeetingLog to a single completion flag per meeting
meeting_completion AS (
    SELECT
        rm.TutorID,
        rm.MeetingID,
        MAX(CASE WHEN ml.MeetingStatus = 'completed' THEN 1 ELSE 0 END) AS IsCompleted
    FROM required_meetings rm
    LEFT JOIN MeetingLog ml ON ml.MeetingID = rm.MeetingID
    GROUP BY rm.TutorID, rm.MeetingID
)

-- KPI per tutor
SELECT
    t.TutorID,
    t.TutorName,
    COUNT(mc.MeetingID) AS RequiredMeetings,
    SUM(mc.IsCompleted) AS CompletedMeetings,
    (COUNT(mc.MeetingID) - SUM(mc.IsCompleted)) AS NotCompletedMeetings,
    ROUND(100 * SUM(mc.IsCompleted) / NULLIF(COUNT(mc.MeetingID), 0), 2) AS CompletedPercent
FROM meeting_completion mc
JOIN Tutor t ON t.TutorID = mc.TutorID
GROUP BY t.TutorID, t.TutorName
ORDER BY CompletedPercent DESC, t.TutorName;

-- management can see % of students completed required meetings
WITH
-- all required meetings for each student, de-duplicated
required_meetings AS (
    -- individual meetings 
    SELECT DISTINCT a.StudentID, sm.MeetingID
    FROM Allocation a
    JOIN ScheduledMeeting sm
      ON sm.AllocationID = a.AllocationID
    WHERE sm.Required = 1

    UNION

    -- group meetings
    SELECT DISTINCT a.StudentID, sm.MeetingID
    FROM Allocation a
    JOIN ScheduledMeeting sm
      ON sm.GroupID = a.GroupID
    WHERE sm.Required = 1
),

-- reduce MeetingLog rows to one row per meeting, 1 if any log is 'completed'
meeting_completion AS (
    SELECT
        rm.StudentID,
        rm.MeetingID,
        MAX(CASE WHEN ml.MeetingStatus = 'completed' THEN 1 ELSE 0 END) AS IsCompleted
    FROM required_meetings rm
    LEFT JOIN MeetingLog ml
      ON ml.MeetingID = rm.MeetingID
    GROUP BY rm.StudentID, rm.MeetingID
)

-- KPI per student 
SELECT
    s.StudentID,
    CONCAT(s.Forename, ' ', s.Surname) AS StudentFullName,
    COUNT(mc.MeetingID) AS RequiredMeetings,
    SUM(mc.IsCompleted) AS CompletedMeetings,
    (COUNT(mc.MeetingID) - SUM(mc.IsCompleted)) AS NotCompletedMeetings,
    ROUND(100 * SUM(mc.IsCompleted) / NULLIF(COUNT(mc.MeetingID), 0), 2) AS CompletedPercent
FROM meeting_completion mc
JOIN Student s
  ON s.StudentID = mc.StudentID
GROUP BY s.StudentID, StudentFullName
ORDER BY CompletedPercent DESC, StudentFullName;

-- management can see students assigned to tutors
SELECT
    t.TutorName,
    t.StaffEmail,
    GROUP_CONCAT(DISTINCT CONCAT(s.Forename, ' ', s.Surname)
                 ORDER BY s.Surname, s.Forename
                 SEPARATOR ', ') AS StudentNames,
    COUNT(DISTINCT s.StudentID) AS StudentCount
FROM Tutor AS t
LEFT JOIN Allocation AS a ON a.TutorID = t.TutorID
LEFT JOIN Student AS s ON s.StudentID = a.StudentID
GROUP BY t.TutorName, t.StaffEmail
ORDER BY t.TutorName;

-- management can see who is involved in logged meetings
WITH
-- unique tutor–meeting pairs from both paths
tutor_meetings AS (
    -- individual meetings
    SELECT DISTINCT
        a.TutorID,
        t.TutorName,
        sm.MeetingID,
        sm.MeetingType,
        sm.ScheduledDateTime,
        sm.InPerson,
        sm.AllocationID,
        sm.GroupID
    FROM Allocation a
    JOIN Tutor t ON t.TutorID = a.TutorID
    JOIN ScheduledMeeting sm ON sm.AllocationID = a.AllocationID

    UNION 

    -- group meetings
    SELECT DISTINCT
        a.TutorID,
        t.TutorName,
        sm.MeetingID,
        sm.MeetingType,
        sm.ScheduledDateTime,
        sm.InPerson,
        sm.AllocationID,
        sm.GroupID
    FROM Allocation a
    JOIN Tutor t ON t.TutorID = a.TutorID
    JOIN ScheduledMeeting sm ON sm.GroupID = a.GroupID
),

-- one row per tutor - meeting log, without multiplication
logs AS (
    SELECT DISTINCT
        tm.TutorID,
        tm.TutorName,
        ml.LogID,
        ml.MeetingID,
        ml.MeetingStatus,
        ml.RescheduledDateTime,
        ml.DurationMinutes,
        ml.MeetingTopic,
        ml.MeetingNotes,
        ml.ReferralMade,
        tm.MeetingType,
        tm.ScheduledDateTime,
        tm.InPerson,
        tm.AllocationID,
        tm.GroupID
    FROM tutor_meetings tm
    JOIN MeetingLog ml ON ml.MeetingID = tm.MeetingID
)

-- final projection with participant names
SELECT
    l.TutorName,
    l.LogID,
    l.MeetingID,
    l.MeetingStatus,
    l.RescheduledDateTime,
    l.DurationMinutes,
    l.MeetingTopic,
    l.MeetingNotes,
    l.ReferralMade,
    l.MeetingType,
    l.ScheduledDateTime,
    l.InPerson,
    CASE
        WHEN l.GroupID IS NOT NULL THEN (
            SELECT GROUP_CONCAT(DISTINCT CONCAT(s2.Forename, ' ', s2.Surname)
                                ORDER BY s2.Surname, s2.Forename
                                SEPARATOR ', ')
            FROM Allocation a2
            JOIN Student s2 ON s2.StudentID = a2.StudentID
            WHERE a2.GroupID = l.GroupID
        )
        ELSE (
            SELECT CONCAT(s1.Forename, ' ', s1.Surname)
            FROM Allocation a1
            JOIN Student s1 ON s1.StudentID = a1.StudentID
            WHERE a1.AllocationID = l.AllocationID
            LIMIT 1
        )
    END AS MeetingParticipants
FROM logs l
ORDER BY l.ScheduledDateTime, l.MeetingID, l.LogID;

-- management can see total referrals made per type
(
  -- total row
  SELECT
      'TOTAL' AS ReferralType,
      tot.TotalReferrals AS ReferralCount,
      100.00 AS PercentOfTotal
  FROM (SELECT COUNT(*) AS TotalReferrals FROM Referral) AS tot
)

UNION ALL

(
  -- per-type breakdown, includes types with 0 referrals
  SELECT
      t.ReferralType,
      COUNT(r.LogID) AS ReferralCount,
      ROUND(
        100.0 * COUNT(r.LogID) / NULLIF(tot.TotalReferrals, 0),
        2
      ) AS PercentOfTotal
  FROM (
      SELECT 'academic' AS ReferralType UNION ALL
      SELECT 'pastoral' UNION ALL
      SELECT 'employability' UNION ALL
      SELECT 'financial' UNION ALL
      SELECT 'safeguarding'
  ) AS t
  LEFT JOIN Referral AS r ON r.ReferralType = t.ReferralType
  CROSS JOIN (SELECT COUNT(*) AS TotalReferrals FROM Referral) AS tot
  GROUP BY t.ReferralType, tot.TotalReferrals
)

ORDER BY ReferralCount DESC;

-- management can see total esculation cases
(
  -- escalated count + list of all involved student names 
  SELECT
      'Escalated' AS Metric,
      COUNT(*) AS ReferralCount,
      (
        SELECT GROUP_CONCAT(DISTINCT CONCAT(s.Forename, ' ', s.Surname)
                            ORDER BY s.Surname, s.Forename
                            SEPARATOR ', ')
        FROM (
          -- students from individual meetings that had an escalated referral
          SELECT aInd.StudentID
          FROM Referral r2
          JOIN MeetingLog ml2 ON ml2.LogID = r2.LogID
          JOIN ScheduledMeeting sm2 ON sm2.MeetingID = ml2.MeetingID
          JOIN Allocation aInd ON aInd.AllocationID = sm2.AllocationID
          WHERE r2.ReferralStatus = 'escalated'

          UNION

          -- students from group meetings that had an escalated referral 
          SELECT aGrp.StudentID
          FROM Referral r2
          JOIN MeetingLog ml2 ON ml2.LogID = r2.LogID
          JOIN ScheduledMeeting sm2 ON sm2.MeetingID = ml2.MeetingID
          JOIN Allocation aGrp ON aGrp.GroupID = sm2.GroupID
          WHERE r2.ReferralStatus = 'escalated'
        ) AS escalated_students
        JOIN Student s ON s.StudentID = escalated_students.StudentID
      ) AS EscalatedStudentNames
  FROM Referral r
  WHERE r.ReferralStatus = 'escalated'
)

UNION ALL

(
  -- not escalated count
  SELECT
      'Not escalated' AS Metric,
      SUM(CASE WHEN r.ReferralStatus <> 'escalated' OR r.ReferralStatus IS NULL THEN 1 ELSE 0 END) AS ReferralCount,
      NULL AS EscalatedStudentNames
  FROM Referral r
)

ORDER BY (Metric = 'Escalated') DESC;

-- management can see multiple missed meetings
-- individual meetings
SELECT
    t.TutorName,
    CONCAT(s.Forename, ' ', s.Surname) AS ParticipantNames,
    SUM(CASE WHEN ml.MeetingStatus = 'cancelled'   THEN 1 ELSE 0 END) AS CancelledCount,
    SUM(CASE WHEN ml.MeetingStatus = 'rescheduled' THEN 1 ELSE 0 END) AS RescheduledCount,
    SUM(CASE WHEN ml.MeetingStatus IN ('cancelled','rescheduled') THEN 1 ELSE 0 END) AS MissedCount,
    'Individual' AS MeetingScope
FROM MeetingLog AS ml
JOIN ScheduledMeeting AS sm ON sm.MeetingID = ml.MeetingID
JOIN Allocation AS a ON a.AllocationID = sm.AllocationID 
JOIN Tutor AS t ON t.TutorID = a.TutorID
JOIN Student AS s ON s.StudentID = a.StudentID
WHERE ml.MeetingStatus IN ('cancelled','rescheduled')
GROUP BY t.TutorName, s.StudentID, ParticipantNames
HAVING SUM(CASE WHEN ml.MeetingStatus IN ('cancelled','rescheduled') THEN 1 ELSE 0 END) > 1

UNION ALL

-- group meetings
SELECT
    t.TutorName,
    (
      SELECT GROUP_CONCAT(DISTINCT CONCAT(s2.Forename, ' ', s2.Surname)
                          ORDER BY s2.Surname, s2.Forename
                          SEPARATOR ', ')
      FROM Allocation AS a2
      JOIN Student AS s2 ON s2.StudentID = a2.StudentID
      WHERE a2.GroupID = sm.GroupID
    ) AS ParticipantNames,
    SUM(CASE WHEN ml.MeetingStatus = 'cancelled'   THEN 1 ELSE 0 END) AS CancelledCount,
    SUM(CASE WHEN ml.MeetingStatus = 'rescheduled' THEN 1 ELSE 0 END) AS RescheduledCount,
    SUM(CASE WHEN ml.MeetingStatus IN ('cancelled','rescheduled') THEN 1 ELSE 0 END) AS MissedCount,
    'Group' AS MeetingScope
FROM MeetingLog AS ml
JOIN ScheduledMeeting AS sm ON sm.MeetingID = ml.MeetingID
JOIN Allocation AS a ON a.GroupID = sm.GroupID 
JOIN Tutor AS t ON t.TutorID = a.TutorID
WHERE ml.MeetingStatus IN ('cancelled','rescheduled')
GROUP BY t.TutorName, sm.GroupID
HAVING SUM(CASE WHEN ml.MeetingStatus IN ('cancelled','rescheduled') THEN 1 ELSE 0 END) > 1

ORDER BY MeetingScope, TutorName, ParticipantNames;

-- management can see which tutors and students are in the same department
-- tutors
SELECT
    'Tutor' AS Role,
    t.TutorID AS PersonID,
    t.TutorName AS PersonName,
    d.DepartmentName,
    NULL AS CourseName
FROM Tutor AS t
JOIN Department AS d ON d.DepartmentID = t.DepartmentID

UNION ALL

-- students
SELECT
    'Student' AS Role,
    s.StudentID AS PersonID,
    CONCAT(s.Forename, ' ', s.Surname) AS PersonName,
    d.DepartmentName,
    c.CourseName
FROM Student AS s
JOIN Course  AS c ON c.CourseID = s.CourseID
JOIN Department AS d ON d.DepartmentID = c.DepartmentID

ORDER BY Role, PersonID, DepartmentName;

-- management can reassign tutees if needed
UPDATE Allocation
SET TutorID = 7
WHERE AllocationID = 50;

UPDATE Allocation
SET TutorID = 10
WHERE AllocationID = 35;

-- management can delete students from system
DELETE FROM Student
WHERE StudentID = 1;

