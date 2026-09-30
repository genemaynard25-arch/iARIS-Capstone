<?php

namespace App\Http\Controllers;

class ApplicantController extends Controller
{
    /**
     * Applicants list.
     *
     * Everything below is placeholder data so the UI can be built first (same idea
     * as HomeController). The names are made up. When the backend is ready, replace
     * $applicants with a query on the applicants table and keep the same keys, so
     * the Blade view doesn't need to change.
     *
     * 'unit' is 'college' or 'is' (Integrated School) and decides which tab a row
     * appears in. 'status' is one of: Pending, For Exam, Paid, Enrolled.
     */
    public function index()
    {
        return view('applicants.index', [
            'scope' => 'all',
            'academicYear' => '2025–2026',
            'periodLabel' => 'June 2026',
            'applicants' => self::sample(),
        ]);
    }

    /**
     * The placeholder applicants. Public and static so other pages
     * (like College Records) can reuse the same made-up people.
     */
    public static function sample(): array
    {
        return [
            // College
            ['id' => 'APP-2026-0381', 'unit' => 'college', 'last' => 'Santos', 'first' => 'Maria Isabel', 'mi' => 'L', 'college' => 'CITE', 'program' => 'BS Computer Science', 'status' => 'Enrolled', 'applied' => '2026-05-14', 'updated' => '2026-06-08 09:14', 'dob' => '2005-03-03', 'gender' => 'Female', 'email' => 'm.santos@example.com', 'contact' => '0917 000 0381', 'address' => 'Lipa City, Batangas', 'exam' => 'Jun 15, 2026 · 8:00 AM', 'paid' => '2026-06-03', 'amount' => 2000],
            ['id' => 'APP-2026-0382', 'unit' => 'college', 'last' => 'Reyes', 'first' => 'Joshua', 'mi' => 'D', 'college' => 'CITE', 'program' => 'BS Information Technology', 'status' => 'Paid', 'applied' => '2026-05-16', 'updated' => '2026-06-08 10:02', 'dob' => '2004-07-22', 'gender' => 'Male', 'email' => 'j.reyes@example.com', 'contact' => '0917 000 0382', 'address' => 'Batangas City, Batangas', 'exam' => 'Jun 18, 2026 · 9:00 AM', 'paid' => '2026-06-05', 'amount' => 2000],
            ['id' => 'APP-2026-0383', 'unit' => 'college', 'last' => 'Dela Cruz', 'first' => 'Ana Patricia', 'mi' => 'M', 'college' => 'CBEAM', 'program' => 'BS Accountancy', 'status' => 'For Exam', 'applied' => '2026-05-18', 'updated' => '2026-06-07 15:45', 'dob' => '2005-01-10', 'gender' => 'Female', 'email' => 'a.delacruz@example.com', 'contact' => '0917 000 0383', 'address' => 'San Jose, Batangas', 'exam' => 'Jun 20, 2026 · 8:00 AM', 'paid' => null, 'amount' => null],
            ['id' => 'APP-2026-0384', 'unit' => 'college', 'last' => 'Garcia', 'first' => 'Mark Anthony', 'mi' => 'R', 'college' => 'CIHTM', 'program' => 'BS Tourism Management', 'status' => 'Enrolled', 'applied' => '2026-05-20', 'updated' => '2026-06-07 13:20', 'dob' => '2004-09-05', 'gender' => 'Male', 'email' => 'm.garcia@example.com', 'contact' => '0917 000 0384', 'address' => 'Lipa City, Batangas', 'exam' => 'Jun 12, 2026 · 10:00 AM', 'paid' => '2026-06-01', 'amount' => 2000],
            ['id' => 'APP-2026-0385', 'unit' => 'college', 'last' => 'Villareal', 'first' => 'Pia', 'mi' => 'V', 'college' => 'CITE', 'program' => 'BS Computer Science', 'status' => 'Pending', 'applied' => '2026-05-22', 'updated' => '2026-06-06 14:30', 'dob' => '2005-04-18', 'gender' => 'Female', 'email' => 'p.villareal@example.com', 'contact' => '0917 000 0385', 'address' => 'Tanauan City, Batangas', 'exam' => null, 'paid' => null, 'amount' => null],
            ['id' => 'APP-2026-0386', 'unit' => 'college', 'last' => 'Mendoza', 'first' => 'Carlo', 'mi' => 'B', 'college' => 'CEAS', 'program' => 'BS Psychology', 'status' => 'For Exam', 'applied' => '2026-05-23', 'updated' => '2026-06-06 11:05', 'dob' => '2005-11-30', 'gender' => 'Male', 'email' => 'c.mendoza@example.com', 'contact' => '0917 000 0386', 'address' => 'Malvar, Batangas', 'exam' => 'Jun 21, 2026 · 1:00 PM', 'paid' => null, 'amount' => null],
            ['id' => 'APP-2026-0387', 'unit' => 'college', 'last' => 'Aquino', 'first' => 'Bea', 'mi' => 'S', 'college' => 'College of Nursing', 'program' => 'BS Nursing', 'status' => 'Paid', 'applied' => '2026-05-24', 'updated' => '2026-06-05 16:40', 'dob' => '2005-06-14', 'gender' => 'Female', 'email' => 'b.aquino@example.com', 'contact' => '0917 000 0387', 'address' => 'Rosario, Batangas', 'exam' => 'Jun 14, 2026 · 8:00 AM', 'paid' => '2026-06-04', 'amount' => 2000],
            ['id' => 'APP-2026-0388', 'unit' => 'college', 'last' => 'Ramos', 'first' => 'Kevin', 'mi' => 'T', 'college' => 'CBEAM', 'program' => 'BS Business Administration', 'status' => 'Pending', 'applied' => '2026-05-25', 'updated' => '2026-06-05 09:10', 'dob' => '2004-12-02', 'gender' => 'Male', 'email' => 'k.ramos@example.com', 'contact' => '0917 000 0388', 'address' => 'Padre Garcia, Batangas', 'exam' => null, 'paid' => null, 'amount' => null],
            ['id' => 'APP-2026-0389', 'unit' => 'college', 'last' => 'Torres', 'first' => 'Nicole', 'mi' => 'A', 'college' => 'CEAS', 'program' => 'AB Communication', 'status' => 'Enrolled', 'applied' => '2026-05-26', 'updated' => '2026-06-04 14:55', 'dob' => '2005-02-27', 'gender' => 'Female', 'email' => 'n.torres@example.com', 'contact' => '0917 000 0389', 'address' => 'Lipa City, Batangas', 'exam' => 'Jun 10, 2026 · 9:00 AM', 'paid' => '2026-05-30', 'amount' => 2000],
            ['id' => 'APP-2026-0390', 'unit' => 'college', 'last' => 'Castillo', 'first' => 'Paolo', 'mi' => 'J', 'college' => 'CITE', 'program' => 'BS Computer Engineering', 'status' => 'For Exam', 'applied' => '2026-05-27', 'updated' => '2026-06-04 10:20', 'dob' => '2005-08-09', 'gender' => 'Male', 'email' => 'p.castillo@example.com', 'contact' => '0917 000 0390', 'address' => 'Cuenca, Batangas', 'exam' => 'Jun 22, 2026 · 8:00 AM', 'paid' => null, 'amount' => null],
            ['id' => 'APP-2026-0391', 'unit' => 'college', 'last' => 'Navarro', 'first' => 'Janine', 'mi' => 'C', 'college' => 'CIHTM', 'program' => 'BS Hospitality Management', 'status' => 'Paid', 'applied' => '2026-05-28', 'updated' => '2026-06-03 15:15', 'dob' => '2005-05-19', 'gender' => 'Female', 'email' => 'j.navarro@example.com', 'contact' => '0917 000 0391', 'address' => 'Ibaan, Batangas', 'exam' => 'Jun 13, 2026 · 1:00 PM', 'paid' => '2026-06-02', 'amount' => 2000],
            ['id' => 'APP-2026-0392', 'unit' => 'college', 'last' => 'Bautista', 'first' => 'Luis', 'mi' => 'E', 'college' => 'College of Law', 'program' => 'Juris Doctor', 'status' => 'Pending', 'applied' => '2026-05-29', 'updated' => '2026-06-03 08:45', 'dob' => '2001-10-11', 'gender' => 'Male', 'email' => 'l.bautista@example.com', 'contact' => '0917 000 0392', 'address' => 'Lipa City, Batangas', 'exam' => null, 'paid' => null, 'amount' => null],

            // Integrated School
            ['id' => 'APP-IS-2026-0101', 'unit' => 'is', 'last' => 'Lim', 'first' => 'Ethan', 'mi' => 'G', 'level' => 'Grade 11', 'program' => 'STEM', 'status' => 'Enrolled', 'applied' => '2026-05-10', 'updated' => '2026-06-08 08:30', 'dob' => '2010-03-03', 'gender' => 'Male', 'email' => 'parent.lim@example.com', 'contact' => '0917 000 0101', 'address' => 'Lipa City, Batangas', 'exam' => 'Jun 10, 2026 · 8:00 AM', 'paid' => '2026-06-01', 'amount' => 1500],
            ['id' => 'APP-IS-2026-0102', 'unit' => 'is', 'last' => 'Soriano', 'first' => 'Andrea', 'mi' => 'P', 'level' => 'Grade 11', 'program' => 'ABM', 'status' => 'Paid', 'applied' => '2026-05-12', 'updated' => '2026-06-08 09:00', 'dob' => '2010-04-18', 'gender' => 'Female', 'email' => 'parent.soriano@example.com', 'contact' => '0917 000 0102', 'address' => 'Tanauan City, Batangas', 'exam' => 'Jun 14, 2026 · 9:00 AM', 'paid' => '2026-06-03', 'amount' => 1500],
            ['id' => 'APP-IS-2026-0103', 'unit' => 'is', 'last' => 'Pascual', 'first' => 'Miguel', 'mi' => 'F', 'level' => 'Grade 7', 'program' => 'Junior High School', 'status' => 'For Exam', 'applied' => '2026-05-13', 'updated' => '2026-06-07 10:10', 'dob' => '2014-01-21', 'gender' => 'Male', 'email' => 'parent.pascual@example.com', 'contact' => '0917 000 0103', 'address' => 'Lipa City, Batangas', 'exam' => 'Jun 19, 2026 · 8:00 AM', 'paid' => null, 'amount' => null],
            ['id' => 'APP-IS-2026-0104', 'unit' => 'is', 'last' => 'Del Rosario', 'first' => 'Sofia', 'mi' => 'H', 'level' => 'Kindergarten', 'program' => 'Kindergarten', 'status' => 'Pending', 'applied' => '2026-05-15', 'updated' => '2026-06-06 13:25', 'dob' => '2021-07-08', 'gender' => 'Female', 'email' => 'parent.delrosario@example.com', 'contact' => '0917 000 0104', 'address' => 'Mataas na Kahoy, Batangas', 'exam' => null, 'paid' => null, 'amount' => null],
            ['id' => 'APP-IS-2026-0105', 'unit' => 'is', 'last' => 'Francisco', 'first' => 'Gabriel', 'mi' => 'N', 'level' => 'Grade 11', 'program' => 'HUMSS', 'status' => 'Enrolled', 'applied' => '2026-05-17', 'updated' => '2026-06-05 11:50', 'dob' => '2010-09-12', 'gender' => 'Male', 'email' => 'parent.francisco@example.com', 'contact' => '0917 000 0105', 'address' => 'San Juan, Batangas', 'exam' => 'Jun 11, 2026 · 8:00 AM', 'paid' => '2026-06-02', 'amount' => 1500],
            ['id' => 'APP-IS-2026-0106', 'unit' => 'is', 'last' => 'Morales', 'first' => 'Claire', 'mi' => 'D', 'level' => 'Grade 7', 'program' => 'Junior High School', 'status' => 'Paid', 'applied' => '2026-05-19', 'updated' => '2026-06-04 15:35', 'dob' => '2014-05-30', 'gender' => 'Female', 'email' => 'parent.morales@example.com', 'contact' => '0917 000 0106', 'address' => 'Lipa City, Batangas', 'exam' => 'Jun 16, 2026 · 9:00 AM', 'paid' => '2026-06-04', 'amount' => 1500],
            ['id' => 'APP-IS-2026-0107', 'unit' => 'is', 'last' => 'Evangelista', 'first' => 'Lucas', 'mi' => 'R', 'level' => 'Grade 1', 'program' => 'Grade School', 'status' => 'Enrolled', 'applied' => '2026-05-11', 'updated' => '2026-06-06 10:40', 'dob' => '2019-02-14', 'gender' => 'Male', 'email' => 'parent.evangelista@example.com', 'contact' => '0917 000 0107', 'address' => 'Lipa City, Batangas', 'exam' => 'Jun 9, 2026 · 9:00 AM', 'paid' => '2026-05-30', 'amount' => 1500],
            ['id' => 'APP-IS-2026-0108', 'unit' => 'is', 'last' => 'Manalo', 'first' => 'Isabela', 'mi' => 'C', 'level' => 'Grade 4', 'program' => 'Grade School', 'status' => 'For Exam', 'applied' => '2026-05-20', 'updated' => '2026-06-05 14:15', 'dob' => '2016-08-03', 'gender' => 'Female', 'email' => 'parent.manalo@example.com', 'contact' => '0917 000 0108', 'address' => 'Rosario, Batangas', 'exam' => 'Jun 20, 2026 · 9:00 AM', 'paid' => null, 'amount' => null],
            ['id' => 'APP-IS-2026-0109', 'unit' => 'is', 'last' => 'Ocampo', 'first' => 'Nathan', 'mi' => 'L', 'level' => 'Nursery', 'program' => 'Preschool', 'status' => 'Paid', 'applied' => '2026-05-21', 'updated' => '2026-06-04 09:05', 'dob' => '2022-01-27', 'gender' => 'Male', 'email' => 'parent.ocampo@example.com', 'contact' => '0917 000 0109', 'address' => 'Lipa City, Batangas', 'exam' => 'Jun 13, 2026 · 9:00 AM', 'paid' => '2026-06-03', 'amount' => 1500],
            ['id' => 'APP-IS-2026-0110', 'unit' => 'is', 'last' => 'Salazar', 'first' => 'Bianca', 'mi' => 'T', 'level' => 'Grade 9', 'program' => 'Junior High School', 'status' => 'Pending', 'applied' => '2026-05-24', 'updated' => '2026-06-03 16:20', 'dob' => '2012-06-11', 'gender' => 'Female', 'email' => 'parent.salazar@example.com', 'contact' => '0917 000 0110', 'address' => 'Tanauan City, Batangas', 'exam' => null, 'paid' => null, 'amount' => null],
            ['id' => 'APP-IS-2026-0111', 'unit' => 'is', 'last' => 'Dimaculangan', 'first' => 'Rafael', 'mi' => 'S', 'level' => 'Grade 12', 'program' => 'STEM', 'status' => 'For Exam', 'applied' => '2026-05-26', 'updated' => '2026-06-02 11:30', 'dob' => '2009-10-05', 'gender' => 'Male', 'email' => 'parent.dimaculangan@example.com', 'contact' => '0917 000 0111', 'address' => 'Lipa City, Batangas', 'exam' => 'Jun 23, 2026 · 8:00 AM', 'paid' => null, 'amount' => null],
        ];
    }
}
