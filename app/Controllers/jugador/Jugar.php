<?php namespace App\Controllers\jugador;

use  App\Controllers\Basecontroller;
use  App\Controllers\Usuario;

use App\Models\JornadaModel;
use App\Models\UsuarioModel;
use App\Models\JugadaModel;
use App\Models\PremioModel;
use App\Models\RetiradosModel;

use CodeIgniter\I18n\Time;

class Jugar extends BaseController
{
	public function index()
	{
		$jornadaModel = new JornadaModel();
		$retiradosModel = new RetiradosModel();
		$premioModel = new PremioModel();
		$jugadaModel = new JugadaModel();
		$data['title'] = 'Jugar';
		$data['cantidad_ejemplares'] = $jornadaModel->getJornadaEjemplares();
		$data['ejemplares_retirados'] = $retiradosModel->getJornadaRetirados();	
		$data['premio'] = $premioModel->where('jornada_id',$GLOBALS['jornada_id'])->first();	
		$data['gratis'] = $jugadaModel->contarGratis($GLOBALS['jornada_id']);
		return view('jugador/jugar', $data);
	}  

	public function misJugadas()	{		
		helper('funciones');

		$retiradosModel = new RetiradosModel();
		$premioModel = new PremioModel();
		$jugadaModel = new JugadaModel();
				
		$data['title'] = 'Mis jugadas';			
		$data['premio'] = $premioModel->where('jornada_id',$GLOBALS['jornada_id'])->first();
		if(session('usuario_role') == 'admin'){
			$data['mis_jugadas'] = $jugadaModel->getMisJugadasByUserAdmin(session('usuario'));
		}else{
			$data['mis_jugadas'] = $jugadaModel->getJugadasByUser(session('usuario'));
		}		
		$data['gratis'] = $jugadaModel->contarGratis($GLOBALS['jornada_id']);	
		if($data['mis_jugadas']){
			$data['mis_jugadas'] = ordernarJugadas($data['mis_jugadas']);
		}		
		return view('jugador/mis-jugadas', $data);		
	} 
	
	public function crearJugada() {		
		if(!$GLOBALS['status'] && $GLOBALS['cierre']){//Chequear jornada este abierta
			return redirect()->to('jugar');
		}
		$data = $this->request->getPost();

		//Si es jugada de admin-------------
		if( isset($data['usuario']) &&  $data['usuario'] != '' && session('usuario_role') == 'admin' ) {
			
			if(session('usuario_saldo') < $GLOBALS['coste_jugada']) {//Chequear saldo								
				return redirect()->to('jugar')->with('message', setSwaMessage('','',3));
			}
			$data['jornada_id'] = $GLOBALS['jornada_id'];			
			$data['jugado_por'] = session('usuario');
			$data['fecha_jugada'] = new Time('now', 'America/Caracas', 'en_US');

			$jugadaModel = new JugadaModel();
			$jugadaModel->save($data);
			$this->restarSaldo();
			$this->actualizarPremio();

			$message = setSwaMessage('Jugada Registrada', 'Tu jugada ha sido registrada');	
			return redirect()->to('jugar')->with('message', $message);
		}
		//Si es jugada admin---------------

		if(session('usuario_contador') < 2) {
			if(session('usuario_saldo') < $GLOBALS['coste_jugada']) {//Chequear saldo si no es gratis								
				return redirect()->to('jugar')->with('message', setSwaMessage('','',3));
			}				                          
		}else{
			$data['esGratis'] = 1;			
		}			
		
		$rules = [				  
			'1va_ejemplar' => 'required|is_natural|max_length[2]|less_than_equal_to['.$GLOBALS['1va_ejemplares'].']',  
			'2va_ejemplar' => 'required|is_natural|max_length[2]|less_than_equal_to['.$GLOBALS['2va_ejemplares'].']',  
			'3va_ejemplar' => 'required|is_natural|max_length[2]|less_than_equal_to['.$GLOBALS['3va_ejemplares'].']',  
			'4va_ejemplar' => 'required|is_natural|max_length[2]|less_than_equal_to['.$GLOBALS['4va_ejemplares'].']',  
			'5va_ejemplar' => 'required|is_natural|max_length[2]|less_than_equal_to['.$GLOBALS['5va_ejemplares'].']',  
			'6va_ejemplar' => 'required|is_natural|max_length[2]|less_than_equal_to['.$GLOBALS['6va_ejemplares'].']'
		];

		if(! $this->validate($rules)) {			 
			return redirect()->to('jugar')->with('message', setSwaMessage('','', 2));
		}		
		
		if(!$this->chequearRetirados($data)){//Chequear si hay retirados en los datos
			return redirect()->to('jugar');
		} 
		
		$data['jornada_id'] = $GLOBALS['jornada_id'];
		$data['usuario'] = session('usuario');
		$data['fecha_jugada'] = new Time('now', 'America/Caracas', 'en_US');
		
		$jugadaModel = new JugadaModel();
		$jugadaModel->save($data);

		if(session('usuario_contador') < 2) { 
			$this->restarSaldo();
			$this->actualizarPremio();
			if(session('usuario_referido')) {
				$this->actualizarSaldoReferido();
			}
		}else{			
			$this->actualizarPremio($esGratis = TRUE);//Gratis
		}
		$this->actualizarContador();//Actualizar contador			

		$usuarioController = new Usuario();
		$usuarioController->setUserSession(session('usuario'));

		$message = setSwaMessage('Jugada Registrada', 'Tu jugada ha sido registrada');	
		return redirect()->to('jugar')->with('message', $message);
		
	}

	public function editarJugada() {
		if(!$GLOBALS['status'] && $GLOBALS['cierre']){//Chequear jornada este abierta
			return false;
		}
		if($this->request->isAjax()){
			$data = $this->request->getPost();
			$rules = [				  
				'1va_ejemplar' => 'required|is_natural|max_length[2]|less_than_equal_to['.$GLOBALS['1va_ejemplares'].']',  
				'2va_ejemplar' => 'required|is_natural|max_length[2]|less_than_equal_to['.$GLOBALS['2va_ejemplares'].']',  
				'3va_ejemplar' => 'required|is_natural|max_length[2]|less_than_equal_to['.$GLOBALS['3va_ejemplares'].']',  
				'4va_ejemplar' => 'required|is_natural|max_length[2]|less_than_equal_to['.$GLOBALS['4va_ejemplares'].']',  
				'5va_ejemplar' => 'required|is_natural|max_length[2]|less_than_equal_to['.$GLOBALS['5va_ejemplares'].']',  
				'6va_ejemplar' => 'required|is_natural|max_length[2]|less_than_equal_to['.$GLOBALS['6va_ejemplares'].']'
			];
	
			if(! $this->validate($rules)) {					
				echo 'Datos inválidos';
				return false;
			}	
			if (!$this->chequearRetirados($data)){				
				echo 'Has colocado un ejemplar retirado';
				return false;
			}	

			$jugadaModel = new JugadaModel();
			if(session('usuario_role') == 'admin'){
				$jugadaModel->actualizarJugadaAdmin($data['jugada_id'], $data);
			}else{
				$jugadaModel->actualizarJugada($data['jugada_id'], session('usuario'), $data);
			}			
		
			echo '1';			
		}
	}	

	protected function chequearRetirados(array $data) {
        $retiradosModel = new RetiradosModel();
        $retirados = $retiradosModel->where('jornada_id', $GLOBALS['jornada_id'])->first();

        if( in_array($data['1va_ejemplar'], explode(',', $retirados['1va_retirados'])) ||
            in_array($data['2va_ejemplar'], explode(',', $retirados['2va_retirados'])) ||
            in_array($data['3va_ejemplar'], explode(',', $retirados['3va_retirados'])) ||
            in_array($data['4va_ejemplar'], explode(',', $retirados['4va_retirados'])) ||
            in_array($data['5va_ejemplar'], explode(',', $retirados['5va_retirados'])) ||
            in_array($data['6va_ejemplar'], explode(',', $retirados['6va_retirados'])) 
        )  
        {
            return false;               
        }

        return true;
	}
	
	protected function restarSaldo() {        
        $data['usuario_saldo'] = session('usuario_saldo') - $GLOBALS['coste_jugada'];

        $usuarioModel = new UsuarioModel;
        $usuarioModel->update(session('usuario_id'), $data);
    }

	protected function actualizarPremio($esGratis = FALSE) {
        $premioModel = new PremioModel();
        $premio = $premioModel->where('jornada_id', $GLOBALS['jornada_id'])->first();        

        if(!$esGratis) {//Si no es gratis, sumar coste de la jugada al premio
            $data = [  
                'total_jugadas' => $premio['total_jugadas'] + 1,         
                'total_premio' => $premio['total_premio'] + ($GLOBALS['coste_jugada'] * ($GLOBALS['1er_lugar'] + $GLOBALS['2do_lugar'])),
                '1er_lugar_premio' => $premio['1er_lugar_premio'] + ($GLOBALS['coste_jugada'] * $GLOBALS['1er_lugar']),
                '2do_lugar_premio' => $premio['2do_lugar_premio'] + ($GLOBALS['coste_jugada'] * $GLOBALS['2do_lugar'])
            ];
        }else{
            $data['total_jugadas'] = $premio['total_jugadas'] + 1;
        }       

        $premioModel->update($premio['premio_id'], $data);
	}
	
	protected function actualizarContador() {        

        if(session('usuario_contador') < 2) {
            $data['contador'] = session('usuario_contador') + 1;
        }else {
            $data['contador'] = 0;
        }
        
        $usuarioModel = new UsuarioModel();        
        $usuarioModel->update(session('usuario_id'), $data);        
	} 
	
	protected function actualizarSaldoReferido() {
		$usuarioModel = new UsuarioModel();
		$usuario = $usuarioModel->where('usuario', session('usuario_referido'))->first();
		$newSaldo =  $usuario['usuario_saldo'] + ( $GLOBALS['coste_jugada'] * 
		( 1 - ( $GLOBALS['1er_lugar'] +  $GLOBALS['2do_lugar'] ) )  
		/ 2 ) ;

		$newData = [
			'usuario_id' => $usuario['usuario_id'],
			'usuario_saldo' => $newSaldo
		];

		$usuarioModel->save($newData);
	}
	//--------------------------------------------------------------------	
}