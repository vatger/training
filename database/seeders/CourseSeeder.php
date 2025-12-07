<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\FamiliarisationSector;
use App\Models\Role;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        // Create some sample familiarisation sectors
        $edggSectors = ['KTG', 'RUD', 'BAD', 'FUL'];
        $edmmSectors = ['STG', 'MUN', 'ALB', 'NUR'];
        $edwwSectors = ['HMM', 'BRE', 'HAN', 'PAD'];

        foreach ($edggSectors as $sector) {
            FamiliarisationSector::firstOrCreate([
                'name' => $sector,
                'fir' => 'EDGG',
            ]);
        }

        foreach ($edmmSectors as $sector) {
            FamiliarisationSector::firstOrCreate([
                'name' => $sector,
                'fir' => 'EDMM',
            ]);
        }

        foreach ($edwwSectors as $sector) {
            FamiliarisationSector::firstOrCreate([
                'name' => $sector,
                'fir' => 'EDWW',
            ]);
        }

        // Get mentor groups
        $edggMentor = Role::where('name', 'EDGG Mentor')->first();
        $edmmMentor = Role::where('name', 'EDMM Mentor')->first();
        $edwwMentor = Role::where('name', 'EDWW Mentor')->first();

        // Sample courses data
        $courses = [
            // EDGG Courses
            [
                'name' => 'Frankfurt Tower S2',
                'description' => 'Tower training for Frankfurt Airport (EDDF)',
                'airport_name' => 'Frankfurt',
                'airport_icao' => 'EDDF',
                'mentor_group_id' => $edggMentor?->id,
                'min_rating' => 2,
                'max_rating' => 2,
                'type' => 'RTG',
                'position' => 'TWR',
                'moodle_course_ids' => [101, 102],
            ],
            [
                'name' => 'Frankfurt Approach S3',
                'description' => 'Approach training for Frankfurt Airport (EDDF)',
                'airport_name' => 'Frankfurt',
                'airport_icao' => 'EDDF',
                'mentor_group_id' => $edggMentor?->id,
                'min_rating' => 3,
                'max_rating' => 3,
                'type' => 'RTG',
                'position' => 'APP',
                'moodle_course_ids' => [201, 202],
            ],
            [
                'name' => 'Frankfurt Ground/Delivery Endorsement',
                'description' => 'Endorsement training for Frankfurt Ground and Delivery',
                'airport_name' => 'Frankfurt',
                'airport_icao' => 'EDDF',
                'mentor_group_id' => $edggMentor?->id,
                'min_rating' => 2,
                'max_rating' => 10,
                'type' => 'EDMT',
                'position' => 'GND',
                'moodle_course_ids' => [301],
            ],
            
            // EDMM Courses
            [
                'name' => 'München Tower S2',
                'description' => 'Tower training for München Airport (EDDM)',
                'airport_name' => 'München',
                'airport_icao' => 'EDDM',
                'mentor_group_id' => $edmmMentor?->id,
                'min_rating' => 2,
                'max_rating' => 2,
                'type' => 'RTG',
                'position' => 'TWR',
                'moodle_course_ids' => [103, 104],
            ],
            [
                'name' => 'München Approach S3',
                'description' => 'Approach training for München Airport (EDDM)',
                'airport_name' => 'München',
                'airport_icao' => 'EDDM',
                'mentor_group_id' => $edmmMentor?->id,
                'min_rating' => 3,
                'max_rating' => 3,
                'type' => 'RTG',
                'position' => 'APP',
                'moodle_course_ids' => [203, 204],
            ],
            
            // EDWW Courses
            [
                'name' => 'Hamburg Tower S2',
                'description' => 'Tower training for Hamburg Airport (EDDH)',
                'airport_name' => 'Hamburg',
                'airport_icao' => 'EDDH',
                'mentor_group_id' => $edwwMentor?->id,
                'min_rating' => 2,
                'max_rating' => 2,
                'type' => 'RTG',
                'position' => 'TWR',
                'moodle_course_ids' => [105, 106],
            ],
            [
                'name' => 'Bremen Familiarisation',
                'description' => 'Familiarisation training for Bremen sector',
                'airport_name' => 'Bremen',
                'airport_icao' => 'EDDW',
                'mentor_group_id' => $edwwMentor?->id,
                'min_rating' => 3,
                'max_rating' => 10,
                'type' => 'FAM',
                'position' => 'CTR',
                'familiarisation_sector_id' => FamiliarisationSector::where('name', 'BRE')->where('fir', 'EDWW')->first()?->id,
            ],
            
            // Guest courses
            [
                'name' => 'Frankfurt Visitor Training',
                'description' => 'Visitor training for non-German controllers',
                'airport_name' => 'Frankfurt',
                'airport_icao' => 'EDDF',
                'mentor_group_id' => $edggMentor?->id,
                'min_rating' => 2,
                'max_rating' => 10,
                'type' => 'GST',
                'position' => 'TWR',
                'moodle_course_ids' => [401],
            ],
            
            // Roster reentry
            [
                'name' => 'Roster Reentry Course',
                'description' => 'Course for controllers returning to the roster',
                'airport_name' => 'General',
                'airport_icao' => 'EDXX',
                'mentor_group_id' => $edggMentor?->id,
                'min_rating' => 2,
                'max_rating' => 10,
                'type' => 'RST',
                'position' => 'TWR',
                'moodle_course_ids' => [501],
            ],
        ];

        foreach ($courses as $courseData) {
            Course::firstOrCreate(
                [
                    'airport_icao' => $courseData['airport_icao'],
                    'type' => $courseData['type'],
                    'position' => $courseData['position'],
                ],
                $courseData
            );
        }

        $this->command->info('Sample courses seeded successfully.');
    }
}