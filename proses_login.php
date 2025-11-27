<?php
// proses_login.php
session_start();
require_once "database.php";
 
if($_SERVER["REQUEST_METHOD"] == "POST"){
 
    $username = $_POST["username"];
    $password = $_POST["password"];
    
    $sql = "SELECT id, username, password, role FROM admin WHERE username = ?";
    
    if($stmt = $conn->prepare($sql)){
        $stmt->bind_param("s", $username);
        
        if($stmt->execute()){
            $stmt->store_result();
            
            if($stmt->num_rows == 1){                    
                $stmt->bind_result($id, $username, $stored_password, $role);
                if($stmt->fetch()){
                    if($password === $stored_password){ // Perbandingan teks biasa
                        session_start();
                        
                        $_SESSION["loggedin"] = true;
                        $_SESSION["id"] = $id;
                        $_SESSION["username"] = $username;
                        $_SESSION["role"] = $role;                            
                        
                        header("location: index.php");
                    } else{
                        header("location: login.php?error=1");
                    }
                }
            } else{
                header("location: login.php?error=1");
            }
        } else{
            echo "Oops! Terjadi kesalahan.";
        }
        $stmt->close();
    }
    $conn->close();
}
?>
