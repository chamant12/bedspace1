<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("INSERT INTO cities (district_id, city_name) VALUES
(1, 'Colombo'),
(1, 'Sri Jayawardenepura Kotte'),
(1, 'Dehiwala-Mount Lavinia'),
(1, 'Moratuwa'),
(1, 'Maharagama'),
(2, 'Gampaha'),
(2, 'Negombo'),
(2, 'Katunayake'),
(2, 'Wattala'),
(2, 'Kelaniya'),
(3, 'Kalutara'),
(3, 'Panadura'),
(3, 'Beruwala'),
(3, 'Horana'),
(4, 'Kandy'),
(4, 'Gampola'),
(4, 'Nawalapitiya'),
(4, 'Wattegama'),
(5, 'Matale'),
(5, 'Dambulla'),
(5, 'Sigiriya'),
(6, 'Nuwara Eliya'),
(6, 'Hatton'),
(6, 'Talawakele'),
(7, 'Galle'),
(7, 'Ambalangoda'),
(7, 'Hikkaduwa'),
(8, 'Matara'),
(8, 'Weligama'),
(8, 'Mirissa'),
(9, 'Hambantota'),
(9, 'Tangalle'),
(9, 'Tissamaharama'),
(10, 'Jaffna'),
(10, 'Chavakachcheri'),
(10, 'Point Pedro'),
(12, 'Mannar'),
(13, 'Vavuniya'),
(11, 'Kilinochchi'),
(14, 'Mullaitivu'),
(17, 'Trincomalee'),
(17, 'Nilaveli'),
(17, 'Uppuveli'),
(15, 'Batticaloa'),
(15, 'Kattankudy'),
(15, 'Eravur'),
(16, 'Ampara'),
(16, 'Kalmunai'),
(16, 'Akkaraipattu'),
(20, 'Anuradhapura'),
(21, 'Polonnaruwa'),
(18, 'Kurunegala'),
(18, 'Kuliyapitiya'),
(19, 'Puttalam'),
(19, 'Chilaw'),
(22, 'Badulla'),
(22, 'Bandarawela'),
(23, 'Monaragala'),
(24, 'Ratnapura'),
(24, 'Balangoda'),
(25, 'Kegalle'),
(25, 'Mawanella')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
