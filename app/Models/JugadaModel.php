<?php namespace App\Models;

use CodeIgniter\Model;

class JugadaModel extends Model {
    protected $table      = 'jugadas';
    protected $primaryKey = 'jugada_id';    

    protected $allowedFields = [
        'jornada_id', 'usuario', 'fecha_jugada', 
        '1va_ejemplar','1va_pts','2va_ejemplar',
        '2va_pts','3va_ejemplar','3va_pts',
        '4va_ejemplar','4va_pts','5va_ejemplar',
        '5va_pts','6va_ejemplar','6va_pts',
        'total_pts', 'esGratis'
    ];

    public function getAllByJornadaId(int $jornada_id) {
        $builder = $this->db->table('jugadas');
        $query = $builder
        ->select('jugada_id, jornada_id, usuario, DATE_FORMAT(fecha_jugada, "%H:%i") AS fecha_jugada,    
        1va_ejemplar, 2va_ejemplar, 3va_ejemplar, 4va_ejemplar, 5va_ejemplar, 6va_ejemplar,
        1va_pts, 2va_pts, 3va_pts, 4va_pts, 5va_pts, 6va_pts, total_pts')
        ->where('jornada_id', $jornada_id)
        ->orderBy('total_pts DESC, fecha_jugada DESC')
        ->get()->getResultArray();
        return $query;
    }

    public function getJugadasByUser(string $usuario) {
        $builder = $this->db->table('jugadas');
        $query = $builder
        ->select('jugada_id, jornada_id, DATE_FORMAT(fecha_jugada, "%d/%m") AS fecha_jugada, 1va_ejemplar, 2va_ejemplar, 3va_ejemplar, 4va_ejemplar, 5va_ejemplar, 6va_ejemplar,
        1va_pts, 2va_pts, 3va_pts, 4va_pts, 5va_pts, 6va_pts, total_pts')
        ->where('usuario', $usuario)
        ->orderBy('fecha_jugada DESC')
        ->get()->getResultArray();
        return $query;
    }

    public function actualizarJugada(int $jugada_id, string $usuario, array $data) {
        $builder = $this->db->table('jugadas');
        $builder->where('jugada_id', $jugada_id)
        ->where('usuario', $usuario)
        ->set($data)->update();
    }
     
    public function contarGratis(int $jornada_id) {
        $builder = $this->db->table('jugadas');
        $query = $builder 
        ->where('jornada_id', $jornada_id)       
        ->where('esGratis', 1) 
        ->countAllResults();      
        return $query;
    }
}