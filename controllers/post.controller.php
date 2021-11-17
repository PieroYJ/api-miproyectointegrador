<?php 

use Firebase\JWT\JWT;

class PostController{

	/*=============================================
	Peticion para tomar los nombres de las columnas
	=============================================*/

	static public function getColumnsData($table, $database){

		$response = PostModel::getColumnsData($table, $database);
		return $response;
	
	}

	/*=============================================
	Peticion POST para crear datos
	=============================================*/

	public function postData($table, $data){

		$response = PostModel::postData($table, $data);

		$return = new PostController();
		$return -> fncResponse($response, "postData", null);

	}

	/*=============================================
	Peticion POST para registrar usuario
	=============================================*/

	public function postRegister($table, $data){

		if(isset($data["password_user"]) && $data["password_user"] != null){

			$crypt = crypt($data["password_user"], '$2a$07$azybxcags23425sdg23sdfhsd$');

			$data["password_user"] = $crypt;

			$response = PostModel::postData($table, $data);

			$return = new PostController();
			$return -> fncResponse($response, "postData", null);

		}else{

			$response = PostModel::postData($table, $data);

			if($response == "The process was successful"){

				$user = GetModel::getFilterData($table, "email_user", $data["email_user"],null, null, null, null, "*");

				if(!empty($user)){	

					/*=============================================
					Creación de JWT
					=============================================*/

					$time = time();
					$key = "azscdvfbgnhmjkl1q2w3e4r5t6y7u8i9o";

					$token = array(

						"iat" => $time,  // Tiempo que inició el token
						"exp" => $time + (60*60*24), // Tiempo que expirará el token (+1 dia)
						'data' => [
							"id" =>  $user[0]->id_user,
							"email" =>  $user[0]->email_user
						]
					);

					$jwt = JWT::encode($token, $key);

					/*=============================================
					Actualizamos la base de datos con el Token del usuario
					=============================================*/

					$data = array(
						"token_user" => $jwt,
						"token_exp_user" => $token["exp"]
					);

					$update = PutModel::putData($table, $data, $user[0]->id_user, "id_user");

					if($update == "The process was successful"){

						$return = new PostController();
						$return -> fncResponse($response, "postData", null);

					}

				}
			
			}
	
		}

	}

	/*=============================================
	Peticion POST para el ingreso de usuario
	=============================================*/

	public function postLogin($table, $data){

		$response = GetModel::getFilterData($table, "email_user", $data["email_user"],null, null, null, null, "*");
		
		if(!empty($response)){	

			$ubigeo = 'Not registered yet';
			$country_obj = GetModel::getFilterData('countries', 'id', $response[0]->id_country, null, null, null, null, 'name');

			if (!empty($response[0]->id_country)) 
				$ubigeo = $country_obj[0]->name;

			if (!empty($response[0]->id_district)) 
			{
				$district_obj = GetModel::getFilterData('districts', 'id', $response[0]->id_district, null, null, null, null, '*');
				$province_obj = GetModel::getFilterData('provinces', 'id_province', $district_obj[0]->id_province_district, null, null, null, null, 'name');
				$department_obj = GetModel::getFilterData('departments', 'id_department', $district_obj[0]->id_department_district, null, null, null, null, 'name');

				$ubigeo .= ' / '.$department_obj[0]->name;
				$ubigeo .= ' / '.$province_obj[0]->name;
				$ubigeo .= ' / '.$district_obj[0]->name;
			}

			$phones = 'Not registered yet';
			if (!empty($response[0]->phone1)) 
				$phones = array($response[0]->phone1, $response[0]->phone2, $response[0]->phone3);

			$response[0]->ubigeo = $ubigeo;
			$response[0]->phones = $phones;

			/*=============================================
			Encriptamos la contraseña
			=============================================*/

			$crypt = crypt($data["password_user"], '$2a$07$azybxcags23425sdg23sdfhsd$');

			if($response[0]->password_user == $crypt){

			 	/*=============================================
				Creación de JWT
				=============================================*/

				$time = time();
				$key = "azscdvfbgnhmjkl1q2w3e4r5t6y7u8i9o";

				$token = array(

					"iat" => $time,  // Tiempo que inició el token
					"exp" => $time + (60*60*24), // Tiempo que expirará el token (+1 dia)
					'data' => [
						"id" =>  $response[0]->id_user,
						"email" =>  $response[0]->email_user
					]
				);

				$jwt = JWT::encode($token, $key);

				/*=============================================
				Actualizamos la base de datos con el Token del usuario
				=============================================*/

				$data = array(
					"token_user" => $jwt,
					"token_exp_user" => $token["exp"]
				);

				$update = PutModel::putData($table, $data, $response[0]->id_user, "id_user");

				if($update == "The process was successful"){

					$response[0]->token_user = $jwt;
					$response[0]->token_exp_user = $token["exp"];

					$return = new PostController();
					$return -> fncResponse($response, "postLogin",  null);

				}
	

			}else{

				$response = null;
				$return = new PostController();
				$return -> fncResponse($response, "postLogin",  "Wrong password");

			}

		}else{

			$response = null;
			$return = new PostController();
			$return -> fncResponse($response, "postLogin",  "Wrong email");

		}


	}

	/*=============================================
	Respuestas del controlador
	=============================================*/

	public function fncResponse($response, $method, $error){

		if(!empty($response)){

			/*=============================================
			Quitamos la contraseña de la respuesta
			=============================================*/

			if(isset($response[0]->password_user)){

				unset($response[0]->password_user);
			}

			$json = array(
				'status' => 200,
				"results" => $response
			);

		}else{

			if($error != null){

				$json = array(
					'status' => 400,
					"results" => $error
				);

			}else{

				$json = array(
					'status' => 404,
					"results" => "Not Found",
					'method' => $method
				);

			}

		}

		echo json_encode($json, http_response_code($json["status"]));

		return;

	}


}