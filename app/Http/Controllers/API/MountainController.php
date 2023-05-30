<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Models\Mountain\Mountain;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use App\Models\Mountain\MountainSlope;
use App\Models\Mountain\MountainSkiLift;

class MountainController extends Controller
{
    use Functions;

    /* Get list of all mountains */
    public function getMountainList()
    {
        $mountains = Mountain::get();
        return $this->sendResponse(true, 'Mountain List', $mountains);
    }

    /* Get mountain ski lift */
    public function getMountainSkiLift(Request $request)
    {
        $v = validator($request->all(), [
            'mountain_id' => 'required|integer',
        ]);

        if ($v->fails()) return $this->sendResponse(false, $v->errors()->first());
        $skiLift = MountainSkiLift::where('mountain_id',$request->mountain_id)->get();
        return $this->sendResponse(true, 'Mountain Ski Lift', $skiLift);

    }

    /* Get mountain slopes */
    public function getMountainSlopes(Request $request)
    {
        $v = validator($request->all(), [
            'mountain_id' => 'required|integer',
        ]);

        if ($v->fails()) return $this->sendResponse(false, $v->errors()->first());

        $slopes = MountainSlope::where('mountain_id',$request->mountain_id)->get();
        return $this->sendResponse(true, 'Mountain Slope', $slopes);
    }

    /* Get all  properties response in xml format */
    public function getPropertyInXml()
    {
        $property = Cache::get('property');
        return response($property, 200, [
            'Content-Type' => 'application/xml'
        ]);
    } 

    /* Get all Lift in xml format */
    public function getLiftInXml(Request $request)
    {
        if($request->OmitClosed) {
            $lift =  Cache::get('liftWithOmitClose');
        } else {
            $lift =  Cache::get('lift');
        }
        return response($lift, 200, [
            'Content-Type' => 'application/xml'
        ]);
    } 

    /* Get all slopes in xml format */
    public function getSlopeInXml(Request $request)
    {
        if($request->OmitClosed) {
            $slope =  Cache::get('slopeWithOmitClose');
        } else {
            $slope =  Cache::get('slope');
        }
        return response($slope, 200, [
            'Content-Type' => 'application/xml'
        ]);
    } 

   /*  For backup purpose only */
    public function mountainData()
    {
        /* $ski1 =  [
            [
                'name'=>'A1 Hahnenkammbahn',
                'mountain_id'=>1,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'A2 Ganslern',
                'mountain_id'=>1,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'A3 Walde',
                'mountain_id'=>1,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'A4 Fleckalmbahn',
                'mountain_id'=>1,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'A6 Märchenwald',
                'mountain_id'=>1,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'A7 Starthaus',
                'mountain_id'=>1,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'C1 Steinbergkogel',
                'mountain_id'=>1,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'C2 Ehrenbachhöhe',
                'mountain_id'=>1,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'C3 Jufen',
                'mountain_id'=>1,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'C4 Sonnenrast',
                'mountain_id'=>1,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'D1 Silberstube',
                'mountain_id'=>1,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'D2 Brunn',
                'mountain_id'=>1,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'D3 Pengelstein II',
                'mountain_id'=>1,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'D4 Usterkar',
                'mountain_id'=>1,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'D5 Pengelstein I',
                'mountain_id'=>1,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'D6 Hieslegg',
                'mountain_id'=>1,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'D7 Kasereck',
                'mountain_id'=>1,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'D8 Hochsaukaser',
                'mountain_id'=>1,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'D9 3S-Bahn',
                'mountain_id'=>1,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'E1 Maierlbahn',
                'mountain_id'=>1,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'E2 Ochsalm',
                'mountain_id'=>1,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            
        ]; */

        /* $ski2 =  [
            [
                'name'=>'B1 Hornbahn I',
                'mountain_id'=>2,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'B2 Hornbahn II',
                'mountain_id'=>2,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'B3 Horngipfelbahn',
                'mountain_id'=>2,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'B4 Raintal',
                'mountain_id'=>2,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'B5 Brunelle',
                'mountain_id'=>2,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'B6 Alpenhaus',
                'mountain_id'=>2,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'B7 Hornköpfl',
                'mountain_id'=>2,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'B8 Sun Kid',
                'mountain_id'=>2,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'B9 Eggl',
                'mountain_id'=>2,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'B10 Trattalmmulde',
                'mountain_id'=>2,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'H1 Bichlalm',
                'mountain_id'=>2,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'Schneekatze',
                'mountain_id'=>2,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            
        ]; */
        /* $ski3 =  [
            [
                'name'=>'F1 Wagstättbahn',
                'mountain_id'=>3,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'F3 Hausleiten',
                'mountain_id'=>3,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'F5 Talsen',
                'mountain_id'=>3,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'F6 Bärenbadkogel I',
                'mountain_id'=>3,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'F7 Bärenbadkogel II',
                'mountain_id'=>3,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'F8 Gauxjoch',
                'mountain_id'=>3,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'F9 Trattenbach',
                'mountain_id'=>3,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'G1 Resterhöhe',
                'mountain_id'=>3,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'G2 Pass Thurn',
                'mountain_id'=>3,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'G4 Resterkogel',
                'mountain_id'=>3,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'G5 Hanglalm',
                'mountain_id'=>3,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'G7 Hartkaser',
                'mountain_id'=>3,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'G8 Zweitausender',
                'mountain_id'=>3,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],            [
                'name'=>'G9 Panoramabahn I',
                'mountain_id'=>3,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],            [
                'name'=>'G10 Panoramabahn II',
                'mountain_id'=>3,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],            [
                'name'=>'G11 Sun Kid Resterhöhe',
                'mountain_id'=>3,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            
        ]; */
        /* $ski4 =  [
            [
                'name'=>'E8 Gaisberg',
                'mountain_id'=>4,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
        
            
        ]; */
        /* $ski5 =  [
            [
                'name'=>'A5 Rasmusleiten',
                'mountain_id'=>5,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'A8 Ministreif',
                'mountain_id'=>5,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'A9 Mocking',
                'mountain_id'=>5,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
               
        ]; */
        /* $ski678 =  [
            [
                'name'=>'E7 Schatzerlift (Übungslift)',
                'mountain_id'=>6,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'D10 Übungslift Aschau',
                'mountain_id'=>7,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'D11 Sun Kid Aschau',
                'mountain_id'=>7,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'E5 Übungslift Reith',
                'mountain_id'=>8,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'E6 Übungslift Reith',
                'mountain_id'=>8,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
               
        ]; */
       /*  $slope1 =  [
            [
                'name'=>'16 Streiteck',
                'mountain_id'=>1,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'16a Streiteck',
                'mountain_id'=>1,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],            [
                'name'=>'16b Jufen-Steilhang',
                'mountain_id'=>1,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],            [
                'name'=>'17 Powder Heaven',
                'mountain_id'=>1,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],            [
                'name'=>'18 Sonnenrast',
                'mountain_id'=>1,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],            [
                'name'=>'19 Kapellenabfahrt',
                'mountain_id'=>1,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],            [
                'name'=>'20 Asten',
                'mountain_id'=>1,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],            [
                'name'=>'21 Streif-Familienabfahrt',
                'mountain_id'=>1,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],            [
                'name'=>'21 Walde',
                'mountain_id'=>1,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],            [
                'name'=>'21a Waldehang',
                'mountain_id'=>1,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],[
                'name'=>'21b Seidlalmhang',
                'mountain_id'=>1,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],[
                'name'=>'22 Kampen',
                'mountain_id'=>1,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],[
                'name'=>'23 Griesalm',
                'mountain_id'=>1,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],[
                'name'=>'24 Jufen',
                'mountain_id'=>1,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],[
                'name'=>'25 Fleck',
                'mountain_id'=>1,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],[
                'name'=>'25a Fleck',
                'mountain_id'=>1,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],[
                'name'=>'25b Kaser-Fleck',
                'mountain_id'=>1,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],[
                'name'=>'26 Kaser',
                'mountain_id'=>1,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],[
                'name'=>'26a Kaser-Krien',
                'mountain_id'=>1,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],[
                'name'=>'27 Brunn',
                'mountain_id'=>1,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],[
                'name'=>'27a Brunn',
                'mountain_id'=>1,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],[
                'name'=>'27b Brunn-Kälberwald',
                'mountain_id'=>1,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ], 
            [
                'name'=>'27c Brunn-Steilhang',
                'mountain_id'=>1,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ], 
            [
                'name'=>'28 Silberstube',
                'mountain_id'=>1,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ], 
            [
                'name'=>'28a Silberstube',
                'mountain_id'=>1,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ], 
            [
                'name'=>'29 Kasereck',
                'mountain_id'=>1,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ], 
            [
                'name'=>'29a Kasereck',
                'mountain_id'=>1,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ], 
            [
                'name'=>'30 Pengelstein II',
                'mountain_id'=>1,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ], 
            [
                'name'=>'31 Schroll-Skirast',
                'mountain_id'=>1,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ], 
            [
                'name'=>'32 Hieslegg',
                'mountain_id'=>1,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ], 
            [
                'name'=>'32a Hieslegg',
                'mountain_id'=>1,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ], 
            [
                'name'=>'36 Hahnenkamm',
                'mountain_id'=>1,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ], 
            [
                'name'=>'37 Melkalm',
                'mountain_id'=>1,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ], 
            [
                'name'=>'38 Direttissima',
                'mountain_id'=>1,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ], 
            [
                'name'=>'39 Obwiesen',
                'mountain_id'=>1,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ], 
            [
                'name'=>'40 Kaser Nord',
                'mountain_id'=>1,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ], 
            [
                'name'=>'43 Ochsalm',
                'mountain_id'=>1,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ], 
            [
                'name'=>'55 Hochsaukaser',
                'mountain_id'=>1,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ], 
        ]; */

        /* $slope2 =  [
            [
                'name'=>'1 Brunellenfeld',
                'mountain_id'=>2,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'2 Lärchenhang',
                'mountain_id'=>2,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'2a Pletzerwald',
                'mountain_id'=>2,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'3 Hagstein',
                'mountain_id'=>2,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'4 Raintal',
                'mountain_id'=>2,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'4a Raintal',
                'mountain_id'=>2,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'11 Hornköpfl direkt',
                'mountain_id'=>2,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'12 Rote Teufel Trainingsstrecke',
                'mountain_id'=>2,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'13 Hornköpfl-Süd',
                'mountain_id'=>2,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'14 Hornköpfl',
                'mountain_id'=>2,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'15 Eggl',
                'mountain_id'=>2,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'50 Bichlalm-Standard',
                'mountain_id'=>2,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'Trattalmmulde',
                'mountain_id'=>2,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
        ]; */
        /* $slope3 =  [
            [
                'name'=>'60 Wagstätt/Wurzhöhe',
                'mountain_id'=>3,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'61 Talsen',
                'mountain_id'=>3,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'62 Bärenbadkogel I',
                'mountain_id'=>3,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'63 Bärenbadkogel II',
                'mountain_id'=>3,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'64 Bärenbadkogel II',
                'mountain_id'=>3,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'65 Bärenbadkogel-Nord',
                'mountain_id'=>3,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'66 Jägerwurz',
                'mountain_id'=>3,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'67 Wurzhöhe-Süd',
                'mountain_id'=>3,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'67 Wurzhöhe-Süd',
                'mountain_id'=>3,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'68 Trattenbach',
                'mountain_id'=>3,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'70 Resterhöhe',
                'mountain_id'=>3,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'70a Resterhöhe/ Resterkogel',
                'mountain_id'=>3,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'70b Resterkogel',
                'mountain_id'=>3,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'71 Resterkogel',
                'mountain_id'=>3,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'71a Resterkogel',
                'mountain_id'=>3,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'71b Resterkogel - Hanglalm',
                'mountain_id'=>3,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'72 Hanglalm',
                'mountain_id'=>3,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'72a Hanglalm',
                'mountain_id'=>3,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'72b Hanglalm - Resterkogel',
                'mountain_id'=>3,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'72c Hanglalm - Hartkaser',
                'mountain_id'=>3,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'73 Hartkaser',
                'mountain_id'=>3,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'73a Verbindung - Hanglalm',
                'mountain_id'=>3,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'74 Hartkaser',
                'mountain_id'=>3,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'74a Hartkaser - Zweitausender',
                'mountain_id'=>3,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'75 Zweitausender',
                'mountain_id'=>3,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'75a Zweitausender',
                'mountain_id'=>3,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'76 Pass Thurn Direktabfahrt',
                'mountain_id'=>3,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'77 Breitmoos',
                'mountain_id'=>3,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'Verbindung - Trattenbach',
                'mountain_id'=>3,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'Verbindung - Hausleiten',
                'mountain_id'=>3,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'Verbindung - Resterhöhe',
                'mountain_id'=>3,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
        ]; */
        /* $slope45678 =  [
            [
                'name'=>'41 Gaisberg',
                'mountain_id'=>4,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'Verbindung - Rasmusleiten',
                'mountain_id'=>5,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'Übungslift Kirchberg',
                'mountain_id'=>6,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'Sun Kid Aschau',
                'mountain_id'=>7,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'name'=>'Übungswiese Reith 1',
                'mountain_id'=>8,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ],
  
        ]; */
    
    
    
    }
}
