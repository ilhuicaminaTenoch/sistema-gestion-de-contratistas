<?php

namespace App\Http\Controllers;

use DB;
use PDF;
use App\Empresa;
use App\Gestion;
use App\Utilities;


use App\Contratista;
use App\Http\Requests;
use Milon\Barcode\DNS1D;
use Milon\Barcode\DNS2D;

use Illuminate\Http\Request;
use App\Exports\ContratistasExport;

use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ContratistasCompania;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\ContratistaFormRequest;

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
                //->leftjoin('gestion as d','a.id_contratista','=','d.id_contratista')
                ->select('a.id_contratista', 'a.nombre', 'b.compania', 'b.id_compania', 'c.puesto', 'c.id_puesto', 'a.tipo', 'a.nss', 'a.activo')
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
//        $Habilidades = DB::table('gestion')->where('activo', '=', '1')->get();

        return view("Catalogos.Cat_Contratistas.create", ["Compania" => $Compania, "Puesto" => $Puesto]);
    }

    public function store(Request $request)
    {
        $Contratistas = new Contratista();
        $validator = Validator::make($request->all(), [
            'nombre' => 'required',
            'id_compania' => 'required',
            'tipo' => 'required',
            'id_puesto' => 'required',
            'nss' => [
                'required',
                'unique:contratistas,nss',
                function ($attribute, $value, $fail) use ($request){
                    $isExist = DB::select('call sp_verifica_contratista(?,?)', [$request->nombre, $request->nss]);
                    if (count($isExist) > 0){
                        if($isExist[0]->tipo_baja == 1){
                            $fail("El contratista {$isExist[0]->nombre} se dio de baja por no conformidad por los siguientes motivos : {$isExist[0]->motivos}" );
                        }
                    }

                }
            ],
        ]);

//dd($validator);
        if ($validator->fails()) {
            return redirect('Catalogos/Cat_Contratistas/create')
                        ->withErrors($validator)
                        ->withInput();
        }


        $Contratistas->nombre = $request->nombre;
        $Contratistas->id_compania = $request->id_compania;
        $Contratistas->id_puesto = $request->id_puesto;
        $Contratistas->tipo = $request->tipo;
        $Contratistas->nss = $request->nss;
        $Contratistas->activo = '1';
        $Contratistas->save();

        $Contratistas = DB::table('contratistas as a')
        ->select('a.id_contratista', 'a.nombre', 'a.tipo', 'a.nss', 'a.activo')
        ->orderBy('id_contratista', 'desc')
        ->limit(1)
        ->get();

        $idcontratista=$Contratistas[0]->id_contratista;
        //dd($idcontratista);
        $Habilidades =DB::table('contratistas as a')
                    ->leftjoin('gestion as d','a.id_contratista','=','d.id_contratista')
                    ->select('a.id_contratista','a.nombre','a.tipo', 'a.nss', 'a.activo','d.id_gestion','d.induccion','d.examen_medico','d.diciembre','d.febrero','d.abril', 'd.junio','d.agosto', 'd.octubre', 'd.alturas', 'd.armado_a', 'd.plataforma_e', 'd.gruas_i', 'd.montacargas', 'd.equipo_aux', 'd.maquinaria_p', 'd.e_confinados', 'd.t_caliente', 'd.t_electricos', 'd.loto', 'd.apertura_l', 'd.amoniaco', 'd.quimicos', 'd.temperatura_e', 'd.temperatura_a')
       ->where('a.id_contratista','=',$idcontratista)->get();


        //dd($Contratistas);

        return view("Catalogos.Cat_Contratistas.agregarHNuevo",["idContratista"=>$idcontratista,"Habilidades"=>$Habilidades]);

        //return Redirect::to('Catalogos/Cat_Contratistas');
    }

    public function edit($id)
    {
        $Contratistas = DB::table('contratistas')
                        ->select(
                            DB::raw("DATE_FORMAT(fechaISuspencion, '%Y-%m-%d') as fechaISuspencion"),
                            DB::raw("DATE_FORMAT(fechaFSuspencion, '%Y-%m-%d') as fechaFSuspencion"),
                            'id_contratista',
                            'nombre',
                            'id_compania',
                            'id_puesto',
                            'tipo',
                            'nss',
                            'activo',
                            //'motivos',
                            'suspendido'
                        )
                        ->where('id_contratista', '=', $id)
                        ->get();
        //dd($Contratistas);

        $Compania = DB::table('empresas')->where('activo', '=', '1')->get();
        $Puesto = DB::table('puestos')->where('activo', '=', '1')->get();
        return view("Catalogos.Cat_Contratistas.edit", ["Contratistas" => $Contratistas, "Compania" => $Compania, "Puesto" => $Puesto]);

    }

    public function update(Request $request, $id)
    {
        /*$request->validate([
            'fechaISuspencion' => 'required|date',
            'fechaFSuspencion' => 'required|date',
        ]);*/

        $Contratistas = Contratista::findOrFail($id);
        $Contratistas->nombre = $request->get('nombre');
        $Contratistas->id_compania = $request->get('id_compania');
        $Contratistas->id_puesto = $request->get('id_puesto');
        $Contratistas->tipo = $request->get('tipo');
        $Contratistas->nss = $request->get('nss');
        $Contratistas->suspendido = $request->get('optionsRadios');
        $Contratistas->fechaISuspencion = $request->get('inicio');
        $Contratistas->fechaFSuspencion = $request->get('fin');
        $Contratistas->activo = '1';
        $Contratistas->update();


        return Redirect::to('Catalogos/Cat_Contratistas');

    }


    public function destroyView($id, Request $request)
    {
        $Contratistas = DB::table('contratistas')->where('id_contratista', $id)->first();
        return view("Catalogos.Cat_Contratistas.destroy-view",["contratistas" => $Contratistas]);
    }

    public function activo(Request $request)
    {
        DB::statement('call sp_bajas_contratistas(?,?,?)', [$request->input('idContratista'), $request->input('tipoBaja'), $request->input('motivo')]);

        return Redirect::to('Catalogos/Cat_Contratistas');
    }

    public function agregarH($id)
    {
        $Contratistas=Contratista::findOrFail($id);
        //$Habilidades =DB::table('gestion')->where('id_contratista','=',$id)->get();
       $Habilidades =DB::table('contratistas as a')
                    ->leftjoin('gestion as d','a.id_contratista','=','d.id_contratista')
                    ->select('a.id_contratista','a.nombre','a.tipo', 'a.nss', 'a.activo','d.id_gestion','d.induccion','d.examen_medico','d.diciembre','d.febrero','d.abril', 'd.junio','d.agosto', 'd.octubre', 'd.alturas', 'd.armado_a', 'd.plataforma_e', 'd.gruas_i', 'd.montacargas', 'd.equipo_aux', 'd.maquinaria_p', 'd.e_confinados', 'd.t_caliente', 'd.t_electricos', 'd.loto', 'd.apertura_l', 'd.amoniaco', 'd.quimicos', 'd.temperatura_e', 'd.temperatura_a')
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
                    ->select('a.id_contratista','a.nombre','a.tipo', 'a.nss', 'a.activo','d.id_gestion','d.induccion','d.examen_medico','d.diciembre','d.febrero','d.abril', 'd.junio','d.agosto', 'd.octubre', 'd.alturas', 'd.armado_a', 'd.plataforma_e', 'd.gruas_i', 'd.montacargas', 'd.equipo_aux', 'd.maquinaria_p', 'd.e_confinados', 'd.t_caliente', 'd.t_electricos', 'd.loto', 'd.apertura_l', 'd.amoniaco', 'd.quimicos', 'd.temperatura_e', 'd.temperatura_a')
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
        $compania = $request->compania;
        $tipo = $request->tipo;

        $model = new Contratista();
        //DB::enableQueryLog(); // Enable query log

        $results = DB::select('call sp_reporte_horariosC(?,?,?,?)', [$fechaInicial, $fechaFinal, $compania, $tipo]);

        $page = request('page', 1);
        $pageSize = 10;
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

    public function generatePDF(Request $request){
        $data = base64_decode($request->data);
        $separaVariables = explode('&', $data);
        $fechaInicial = explode('=', $separaVariables[0]);
        $fechaFinal = explode('=', $separaVariables[1]);
        $compania = explode('=', $separaVariables[2]);
        $tipo = explode('=', $separaVariables[3]);
        return Excel::download(new ContratistasCompania($fechaInicial[1], $fechaFinal[1], $compania[1], $tipo[1]), 'HorasHombre.xlsx');


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

    public function reportePdfMedicoInduccion(Request $request){
        $utilities = new Utilities();
        $variables = $utilities->decodeVars($request->data);

        $nombrePdf = 'reporte_medico_induccion'.date('Y-m-d_H:i:s').'.pdf';
        $model = new Contratista();
        $consulta = DB::select('call sp_reporte_vencimiento_accesos(?)', [
            $variables['mes']
        ]);
        $data = ['datum' => $consulta];
        $pdf = PDF::loadView('pdfs.reporte_medico_induccion', $data);
        return $pdf->download($nombrePdf);


    }

    public function obtieneCompanias(Request $request){
        if (!$request->ajax()) return redirect('/');

        //$empresas = new Empresa();
        $empresas = DB::table('empresas')
                        ->where('empresas.activo', '1')
                        ->select('empresas.compania', 'empresas.id_compania')
                        ->orderBy('empresas.compania', 'asc')
                        ->get();
        return [
            'empresas' => $empresas
        ];

    }
}
