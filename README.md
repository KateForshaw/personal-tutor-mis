# Personal Tutor Management Information System (PHP & MySQL)

## Tools & Skills
- **Development:** PHP, MySQL, HTML, CSS, MAMP (local Apache server)
- **Project management:** PMBOK principles, iterative and incremental (agile) development life cycle, Gantt chart, risk register and risk profile
- **Design:** Entity relationship diagram, data dictionaries, user interface designs, system architecture diagram
- **Security:** Role-based access control, password hashing, session timeouts
- **Skills:** Requirements gathering, multi-table SQL queries, CRUD operations, iterative testing, deployment documentation, product costing

## What I Did
This project was the first assignment for the Advanced Professional Practices module of my Data Analytics MSc. Working as a solo developer, I built a web-based system that lets a university track personal tutor meetings, referrals and student support across departments. I developed it over five iterations.

- **Requirements & design:** I defined functional and non-functional requirements (including GDPR-compliant storage and secure role-based access), drew an ERD with 11 entities, wrote a data dictionary for each, and designed dashboards for each user role.
- **Core database:** I built the database in MySQL and populated it with a fictional sample of 10 tutors and 50 students across two departments. I then wrote SQL queries joining up to 5 tables to demonstrate what each role should be able to see, and tested database functionality and CRUD operations.
- **Authentication & roles:** I added a users table with hashed passwords and a login page that sends each role to its own dashboard only. It blocks wrong credentials and attempts to paste in URLs the user isn't allowed to see, and logs users out automatically after 30 minutes.
- **Dashboards:** I built four role-based dashboards. Students see their tutor, meetings and referrals. Tutors can book and log meetings and submit referrals. Managers can create tutor groups and assign or reassign students. Admins can manage users, departments, courses and system settings.
- **Deployment & go-to-market:** I wrote a deployment guide and a system architecture diagram showing the presentation, application and data layers. I also planned how the product could be brought to market, covering ethics, costing, funding and intellectual property.

## Key Findings
- The final system is stable and deployable and meets the core requirements, with all database, CRUD and role-action tests passing.
- Role-based access worked as designed: each user sees only their own data, and attempts to reach other roles' dashboards are blocked.
- An effort-based cost model, combining task duration with complexity ratings, gave a fixed price of £5,350 plus £856 a year for maintenance.
- Each role needed more pages than I first expected. The iterative approach let the scope grow without derailing the project. A future enhancement would be tracking tutors' training.

## Files
| File | Description |
|------|-------------|
| `CIS4509 coursework 1.docx` | Report covering project and risk management, each development iteration and bringing the product to market, with appendices of designs, code and screenshots |
| `Personal Tutor Database.sql` | MySQL script to create and populate the database |
| `PersonalTutorMIS/` | PHP source code for the web application: login and authentication, role dashboards (dashboards/admin, management, tutor and student), shared page includes and CSS styling |
