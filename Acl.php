<?php 
use Phalcon\Mvc\Micro,
Phalcon\Mvc\User\Component;

/**
 * 
 */
class Acl extends Component
{

	public function hola(){



	}

	/*
	 * obtenemos el request en formato Json
	 */
	public function get_request(){

		$app = new Micro();
		return $app->request->getJsonRawBody();

	}

	public function create_user($username,$email,$password){

		$usuario = new Users();
		$usuario->setUsername($username);
		$usuario->setEmail($email);
		$usuario->setPassword($password);
		
		$usuario->save();

	}

	public function create_role($rol){

		$role = new Roles();
		$role->setRole($rol);
		$role->save();
	}

	public function create_persmission($permission_auth,$permission_name){

		$permission = new Permissions();
		$permission->setPermission($permission_auth);
		$permission->setPermissionName($permission_name);
		$permission->save();

	}
	
	public function assign_rol_to_user($id_role,$id_user){

		$rol_user = new	RolesUsers();
		$rol_user->setIdRole($id_role);
		$rol_user->setIdUser($id_user);
		$rol_user->save();

	}

	public function assing_permission_to_role($id_role,$id_permission){

		$role_permission = new RolesPermissions();
		$role_permission->setIdRole($id_role);
		$role_permission->setIdPermission($id_permission);
		$role_permission->save();

	}

	public function assing_permission_to_user($id_user,$id_permission){

		$user_permission = new UsersPermissions();
		$user_permission->setIdUser($id_user);
		$user_permission->setIdPermission($id_permission);
		$user_permission->save();

	}

	public function get_all_permissions(){

		 // $request = $this->get_request();
		 // $id_user = $request->id;	

		 $id_user = 1;


		$role_acos = array();

		$user = Users::findFirst($id_user);
		
		foreach ($user->RolesUsers as $rol_usuario) {

			$roles = Roles::findFirst($rol_usuario->getIdRole());

			foreach ($roles->RolesPermissions as $rol_permission) {

				$role_acos[] = $rol_permission->Permissions->getIdAco();

			} 
		}

		$user_acos = array();
		$user = Users::findFirst($id_user);
		
		foreach ($user->UsersPermissions as $user_permission) {

			$user_acos[] = $user_permission->Permissions->getIdAco();
		}


		$merge_permissions = array_merge($role_acos, $user_acos);

		$result = array_unique($merge_permissions);

		return $result;
	}

	public function auth($app){


		$handler_prop = $this->get_handler_prop($app);
		
		$is_valid_permission = $this->exist_permission_aco($this->exist_aco($handler_prop["aco"],$this->exist_aro($handler_prop["aro"])));

		if (!$is_valid_permission) {
			
			$this->not_authorized($app);
		}

	}

	public function not_authorized($app){

		    $app->stop();
		  
			$response = $app->response;

			$response->setStatusCode(401, "Not Autorized");

            $response->setJsonContent(array('status' => 'Unauthorized', 'message' => "Not Authorized"));

            $response->send();

            $app->finish($app->getActiveHandler());
	}

public function exist_permission_aco($exist_aco){

	if ($exist_aco){

		$id_aco = $exist_aco;

		$user_acos = $this->get_all_permissions();

		if (in_array($id_aco,$user_acos)) {
			
			return true;
		}else{
			return false;
		}
	}

}


public function get_handler_prop($app){

	$active_handler = $app->getActiveHandler(); 
	$aro = str_replace("Controller", "", get_class($active_handler[0]));  

	$aco = $active_handler[1];

	return array("aro"=>$aro,"aco"=>$aco);

}

public function exist_aro($aro){

	$serach_aro_criteria="name = '".$aro."'";

	$exist_aro = Aros::count($serach_aro_criteria);

	if ($exist_aro) {
		$aro_id = $exist_aro;

		$aro_object = Aros::findFirst($serach_aro_criteria);			

		$aro_id = $aro_object->getId();	

		return $aro_id;
	}else{

		return false;
	}

}


public function exist_aco($aco,$aro_id){


	if ($aro_id==false) {
		return false;
	}else{

		$search_aco_criteria =  array(
			"id_aro" => $aro_id ,
			"name" => $aco
			);

		$exist_aco = Acos::count($search_aco_criteria);

		if($exist_aco){

			$aco_object = Acos::findFirst($search_aco_criteria);
			$id_aco = $aco_object->getId();

			return $id_aco;
		}else{

			return false;
		}
	}

}


	public function Acl_db_gen_permissions($app){

		$handlers = $app->getHandlers();
 
        $new_permission_log = array();
        $permission_log = array();
        $current_aro="";
        $id_aro = 0;
        foreach ($handlers as $handler) {
           
           $aro = str_replace("Controller", "", get_class($handler[0])); 
       
           if ($current_aro != $aro) {

               $current_aro = $aro;
               $sc="name ='".$current_aro."'"; 

               //si existe el aro
               if (Aros::count($sc)) {

                 $found_aro = Aros::findFirst($sc);
                 $id_aro = $found_aro->getId();  

               }else{

               $new_aro = new Aros();
               $new_aro->setName($current_aro);
               $new_aro->save();

               $new_aro = Aros::findFirst(array(
                                "order"=>"id",
                                "limit"=>"1"));

               $id_aro= $new_aro->getId();

               }
                
            } 

            $aco = $handler[1];                
           
            $id_aco = 0;
            $sc_acos=array("id_aro='".$id_aro."' and name = '".$aco."'");

            if (!Acos::count($sc_acos)) {


              $new_aco = new Acos();
              $new_aco->setName($aco);
              $new_aco->setIdAro($id_aro);
              $new_aco->save();

              

             }

             $found_aco = Acos::findFirst($sc_acos);
             $id_aco = $found_aco->getId();
          
             $sc_permission="id_aco ='".$id_aco."'"; 

               //si no existe el permiso
             if (!Permissions::count($sc_permission)) {

                    $new_permission = new Permissions();
                    $new_permission->setPermissionName($aro." - ".$aco);
                    $new_permission->setIdAco($id_aco);
                    $new_permission->save();     
                    
                    $new_permission_log[] = $aro." - ".$aco;    
                
                }else{
                    $found_permission = Permissions::findFirst($sc_permission);
                    $permission_log[] = $found_permission->getPermissionName();
                }
        }

        $log = array();
        $log["created_permissions"]=$new_permission_log;
        $log["already_exist_permissions"]=$permission_log;

        return $log;

	}

}