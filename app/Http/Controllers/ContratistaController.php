<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContratistaFormRequest;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Contratista;
use App\Gestion;


use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Crypt;
use Milon\Barcode\DNS1D;

use App\Exports\ContratistasExport;
use Maatwebsite\Excel\Facades\Excel;

use DB;
use Milon\Barcode\DNS2D;
use PDF;

class ContratistaController extends Controller
{
     public function __construct()
    {
        $this->middleware('auth');
    }
    public function index(Request $request)
    {
        if ($request) {

            $query = trim($request->get('searchText'));

            $Contratistas = DB::table('contratistas as a')
                ->join('empresas as b', 'a.id_compania', '=', 'b.id_compania')
                ->join('puestos as c', 'a.id_puesto', '=', 'c.id_puesto')
                ->select('a.id_contratista', 'a.nombre', 'b.compania', 'b.id_compania', 'c.puesto', 'c.id_puesto', 'a.tipo', 'a.RFC', 'a.codigo', 'a.activo')
                ->where('a.nombre', 'LIKE', '%' . $query . '%')
                ->where('a.activo','=',1)
                ->orderBy('id_contratista', 'asc')
                ->paginate(7);

            return view('Catalogos.Cat_Contratistas.index', ["Contratistas" => $Contratistas, "searchText" => $query]);

        }
    }

    public function create()
    {
        $Compania = DB::table('empresas')->where('activo', '=', '1')->get();
        $Puesto = DB::table('puestos')->where('activo', '=', '1')->get();

        return view("Catalogos.Cat_Contratistas.create", ["Compania" => $Compania, "Puesto" => $Puesto]);
    }

    public function store(Request $request)
    {
        $barra = new DNS2D();

        $Contratistas = new Contratista();
        //$Contratistas = $barra->getBarcodeSVG("prueba", "C128");
        //dd($Contratistas);
        //validando existencia
        $valida = DB::table('contratistas')->where([
            ['id_compania', '=', $request->post('id_compania')],
            ['id_puesto', '=', $request->post('id_puesto')],
            ['nombre', '=', $request->post('nombre')],
        ])->count();

        if ($valida == '0') {
            $idContratista = DB::getPdo()->lastInsertId();
            $Contratistas->nombre = $request->post('nombre');
            $Contratistas->id_compania = $request->post('id_compania');
            $Contratistas->id_puesto = $request->post('id_puesto');
            $Contratistas->tipo = $request->post('tipo');
            if ($request->get('RFC') == null)
                $Contratistas->RFC = 'Sin RFC';
            else
                $Contratistas->RFC = $request->get('RFC');

            $Contratistas->activo = '1';
            $Contratistas->codigo = $request->post('nombre');
            $Contratistas->save();

            return Redirect::to('Catalogos/Cat_Contratistas');
        } else {
            return view("Catalogos.Cat_Contratistas.error", ["errores" => "El proyecto ya tiene registrado el contratista"]);

        }


    }

    public function edit($id)
    {
        $Contratistas = Contratista::findOrFail($id);
        //dd($Contratistas);
        $Compania = DB::table('empresas')->where('activo', '=', '1')->get();
        $Puesto = DB::table('puestos')->where('activo', '=', '1')->get();
        return view("Catalogos.Cat_Contratistas.edit", ["Contratistas" => $Contratistas, "Compania" => $Compania, "Puesto" => $Puesto]);

    }

    public function update(Request $request, $id)
    {
        $Contratistas = Contratista::findOrFail($id);

        $Contratistas->nombre = $request->get('nombre');
        $Contratistas->id_compania = $request->get('id_compania');
        $Contratistas->id_puesto = $request->get('id_puesto');
        $Contratistas->tipo = $request->get('tipo');
        $Contratistas->RFC = $request->get('RFC');
        $Contratistas->activo = '1';
        $Contratistas->update();

        return Redirect::to('Catalogos/Cat_Contratistas');

    }

    
    public function destroy($id)
    {
        $Contratistas =Contratista::find($id);
        $Contratistas->delete();
             // $Contratistas = Contratista::findOrFail($id);
             // $Contratistas->Activo = '0';
             // $Contratistas->update();

        
        return Redirect::to('Catalogos/Cat_Contratistas');
    }

    public function agregarH($id)
    {
        $Contratistas=Contratista::findOrFail($id);
        //$Habilidades =DB::table('gestion')->where('id_contratista','=',$id)->get();
       $Habilidades =DB::table('contratistas as a')
                    ->leftjoin('gestion as d','a.id_contratista','=','d.id_contratista')
                    ->select('a.id_contratista','a.nombre','a.tipo', 'a.RFC','a.codigo', 'a.activo','d.id_gestion','d.induccion','d.examen_medico','d.diciembre','d.febrero','d.abril', 'd.junio','d.agosto', 'd.octubre', 'd.alturas', 'd.armado_a', 'd.plataforma_e', 'd.gruas_i', 'd.montacargas', 'd.equipo_aux', 'd.maquinaria_p', 'd.e_confinados', 'd.t_caliente', 'd.t_electricos', 'd.loto', 'd.apertura_l', 'd.amoniaco', 'd.quimicos', 'd.temperatura_e', 'd.temperatura_a')      
       ->where('a.id_contratista','=',$id)->get();
            // dd($Habilidades);
            return view("Catalogos.Cat_Contratistas.agregarH",["Contratistas"=>$Contratistas,"Habilidades"=>$Habilidades]);
    }

    public function updateHabilidad(Request $request, $id)
    {

        $Contratistas = Contratista::findOrFail($id);
        $valida = DB::table('gestion')->where('id_contratista', '=',$id)->count();  

        $Habilidades=DB::table('contratistas as a')
                    ->leftjoin('gestion as d','a.id_contratista','=','d.id_contratista')
                    ->select('a.id_contratista','a.nombre','a.tipo', 'a.RFC','a.codigo', 'a.activo','d.id_gestion','d.induccion','d.examen_medico','d.diciembre','d.febrero','d.abril', 'd.junio','d.agosto', 'd.octubre', 'd.alturas', 'd.armado_a', 'd.plataforma_e', 'd.gruas_i', 'd.montacargas', 'd.equipo_aux', 'd.maquinaria_p', 'd.e_confinados', 'd.t_caliente', 'd.t_electricos', 'd.loto', 'd.apertura_l', 'd.amoniaco', 'd.quimicos', 'd.temperatura_e', 'd.temperatura_a')      
                    ->where('a.id_contratista','=',$id)->get();
           
        $resultD = $request->input('diciembre');

        if($resultD=='on')
        {
            $resultD=1;
        }
        else
        {
            $resultD=0;
        }

        $resultF = $request->input('febrero');
        if($resultF=='on')
            $resultF=1;
        else
            $resultF=0;

        $resultA = $request->input('abril');
        if($resultA=='on')
            $resultA=1;
        else
            $resultA=0;

        $resultJ = $request->input('junio');
        if($resultJ=='on')
            $resultJ=1;
        else
            $resultJ=0;

        $resultAg = $request->input('agosto');
        if($resultAg=='on')
            $resultAg=1;
        else
            $resultAg=0;

        $resultO = $request->input('octubre');
        if($resultO=='on')
            $resultO=1;
        else
            $resultO=0;

        if ($valida!=0) {
            
            $afectadas=DB::table('gestion')
        ->where('id_contratista',$id)
        ->update(['diciembre'=>$resultD
                ,'febrero'=>$resultF
                ,'abril'=>$resultA
                ,'junio'=>$resultJ
                ,'agosto'=>$resultAg
                ,'octubre'=>$resultO
                ,'induccion'=>$request->get('induccion')
                ,'examen_medico'=>$request->get('examen_medico')
                ,'alturas'=>$request->get('alturas')
                ,'armado_a'=>$request->get('armado_a')
                ,'plataforma_e'=>$request->get('plataforma_e')
                ,'gruas_i'=>$request->get('gruas_i')
                ,'montacargas'=>$request->get('montacargas')
                ,'equipo_aux'=>$request->get('equipo_aux')
                ,'maquinaria_p'=>$request->get('maquinaria_p')
                ,'e_confinados'=>$request->get('e_confinados')
                ,'t_caliente'=>$request->get('t_caliente')
                ,'t_electricos'=>$request->get('t_electricos')
                ,'loto'=>$request->get('loto')
                ,'apertura_l'=>$request->get('apertura_l')
                ,'amoniaco'=>$request->get('amoniaco')
                ,'quimicos'=>$request->get('quimicos')
                ,'temperatura_e'=>$request->get('temperatura_e')
                ,'temperatura_a'=>$request->get('temperatura_a')
                ]);
         
        }
        else
        {
            //dd($id);
            // DB::table('gestion')->insert(
            // [   'id_contratista',$id
            //     ,'diciembre'=>$resultD
            //     ,'febrero'=>$resultF
            //     ,'abril'=>$resultA
            //     ,'junio'=>$resultJ
            //     ,'agosto'=>$resultAg
            //     ,'octubre'=>$resultO
            //     ,'induccion'=>$request->get('induccion')
            //     ,'examen_medico'=>$request->get('examen_medico')
            //     ,'alturas'=>$request->get('alturas')
            //     ,'armado_a'=>$request->get('armado_a')
            //     ,'plataforma_e'=>$request->get('plataforma_e')
            //     ,'gruas_i'=>$request->get('gruas_i')
            //     ,'montacargas'=>$request->get('montacargas')
            //     ,'equipo_aux'=>$request->get('equipo_aux')
            //     ,'maquinaria_p'=>$request->get('maquinaria_p')
            //     ,'e_confinados'=>$request->get('e_confinados')
            //     ,'t_caliente'=>$request->get('t_caliente')
            //     ,'t_electricos'=>$request->get('t_electricos')
            //     ,'loto'=>$request->get('loto')
            //     ,'apertura_l'=>$request->get('apertura_l')
            //     ,'amoniaco'=>$request->get('amoniaco')
            //     ,'quimicos'=>$request->get('quimicos')
            //     ,'temperatura_e'=>$request->get('temperatura_e')
            //     ,'temperatura_a'=>$request->get('temperatura_a')
            //     ]);
            //es un insert
            DB::insert('INSERT INTO gestion (id_contratista,diciembre,febrero,abril,junio,agosto,octubre,
                        induccion,examen_medico,alturas,armado_a,plataforma_e,gruas_i,montacargas,equipo_aux, 
                       maquinaria_p,e_confinados,t_caliente,t_electricos,loto,apertura_l,amoniaco,quimicos,
                       temperatura_e,temperatura_a) VALUES (:id_contratista,:diciembre,:febrero,:abril,
                        :junio,:agosto,:octubre,:induccion,:examen_medico,:alturas,:armado_a,:plataforma_e, 
                        :gruas_i,:montacargas,:equipo_aux,:maquinaria_p,:e_confinados,:t_caliente,:t_electricos,
                        :loto,:apertura_l,:amoniaco,:quimicos,:temperatura_e,:temperatura_a)', ['id_contratista' =>$id,'diciembre'=>$resultD,'febrero'=>$resultF,'abril'=>$resultA 
                    ,'junio'=>$resultJ
                ,'agosto'=>$resultAg
                ,'octubre'=>$resultO
                ,'induccion'=>$request->get('induccion')
                ,'examen_medico'=>$request->get('examen_medico')
                ,'alturas'=>$request->get('alturas')
                ,'armado_a'=>$request->get('armado_a')
                ,'plataforma_e'=>$request->get('plataforma_e')
                ,'gruas_i'=>$request->get('gruas_i')
                ,'montacargas'=>$request->get('montacargas')
                ,'equipo_aux'=>$request->get('equipo_aux')
                ,'maquinaria_p'=>$request->get('maquinaria_p')
                ,'e_confinados'=>$request->get('e_confinados')
                ,'t_caliente'=>$request->get('t_caliente')
                ,'t_electricos'=>$request->get('t_electricos')
                ,'loto'=>$request->get('loto')
                ,'apertura_l'=>$request->get('apertura_l')
                ,'amoniaco'=>$request->get('amoniaco')
                ,'quimicos'=>$request->get('quimicos')
                ,'temperatura_e'=>$request->get('temperatura_e')
                ,'temperatura_a'=>$request->get('temperatura_a')]);

        }

        //dd($resultD);
        
        return Redirect::to('Catalogos/Cat_Contratistas');
    }

    public function horarios(){
        return view('Codigos.horarios');
    }

    public function consultaHorarios(Request $request){
        if (!$request->ajax()) return redirect('/');

        $nombre = $request->nombre;
        $fechaInicial = $request->fechaInicio;
        $fechaFinal = $request->fechaTermino;

        $model = new Contratista();
        $sp = DB::select('call sp_reporte_horariosC(?,?,?)', [$fechaInicial, $fechaFinal, $nombre]);
        return $sp;
    }

    public function generatePDF(Request $request){
        $data = base64_decode($request->data);
        $separaVariables = explode('&', $data);
        $fechaInicial = explode('=', $separaVariables[0]);
        $fechaFinal = explode('=', $separaVariables[1]);
        $nombre = explode('=', $separaVariables[2]);

        $nombrePdf = 'horarios_contratistas_'.date('Y-m-d_H:i:s').'.pdf';
        $model = new Contratista();
        $consulta = DB::select('call sp_reporte_horariosC(?,?,?)', [$fechaInicial[1], $fechaFinal[1], $nombre[1]]);
        $data = ['datum' => $consulta];
        $pdf = PDF::loadView('pdfs.myPDF', $data);
        return $pdf->download($nombrePdf);
    }   

    public function examenMedicoInduccion(Request $request){
        return view('Codigos.examenMedicoInduccion');
    }

    public function reporteMedicoInduccion(Request $request){
        if (!$request->ajax()) return redirect('/');

        $mes = $request->mes;
       

        $model = new Contratista();
        $results = DB::select('call sp_reporte_vencimiento_accesos(?)', [$mes]);

        $page = request('page', 1);
        $pageSize = 5;
        $offset = ($page * $pageSize) - $pageSize;
        $data = array_slice($results, $offset, $pageSize, true);
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator($data, count($results), $pageSize, $page);
        return [
            'pagination' => [
                'total'         => $paginator->total(),
                'current_page'  => $paginator->currentPage(),
                'per_page'      => $paginator->perPage(),
                'last_page'     => $paginator->lastPage(),
                'from'          => $paginator->firstItem(),
                'to'            => $paginator->lastItem()
            ],
            'results' => $paginator->items()
        ];        
    }
}
