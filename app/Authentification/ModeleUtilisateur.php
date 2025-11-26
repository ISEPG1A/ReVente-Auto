<?php

class ModeleUtilisateur {
    private $connexion;

    public function __construct() {
        $this->connexion = BaseDeDonnees::obtenirConnexion();
    }

    public function trouverParEmail($email) {
        $stmt = $this->connexion->prepare('SELECT id, first_name, email, avatar_path, password_hash, email_verified_at, phone_verified_at, role FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    public function trouverParId($id) {
        $stmt = $this->connexion->prepare('SELECT id, first_name, last_name, email, phone, avatar_path, email_verified_at, phone_verified_at, role FROM users WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function creer($prenom, $nom, $email, $telephone, $motDePasse, $cheminAvatar = null) {
        $motDePasseHache = CryptoService::hacherMotDePasse($motDePasse);
        $cles = CryptoService::genererPaireCles();
        
        $stmt = $this->connexion->prepare('INSERT INTO users (first_name,last_name,email,phone,password_hash,avatar_path,public_key,private_key,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,NOW(),NOW())');
        
        try {
            $stmt->execute([$prenom, $nom, $email, $telephone, $motDePasseHache, $cheminAvatar, $cles['public'], $cles['private']]);
            return $this->connexion->lastInsertId();
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                throw new Exception('Email déjà utilisé.');
            }
            throw $e;
        }
    }

    public function creerTokenReset($userId, $token) {
        $stmt = $this->connexion->prepare('INSERT INTO password_resets (user_id, token, expires_at, created_at) VALUES (?,?, DATE_ADD(NOW(), INTERVAL 1 HOUR), NOW())');
        return $stmt->execute([$userId, $token]);
    }

    public function verifierTokenReset($token) {
        $stmt = $this->connexion->prepare('SELECT pr.id, pr.user_id FROM password_resets pr WHERE pr.token = ? AND pr.used_at IS NULL AND pr.expires_at > NOW() LIMIT 1');
        $stmt->execute([$token]);
        return $stmt->fetch();
    }

    public function mettreAJourMotDePasse($userId, $nouveauMotDePasse, $resetId = null) {
        $motDePasseHache = CryptoService::hacherMotDePasse($nouveauMotDePasse);
        
        $this->connexion->beginTransaction();
        try {
            $this->connexion->prepare('UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?')->execute([$motDePasseHache, $userId]);
            if ($resetId) {
                $this->connexion->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = ?')->execute([$resetId]);
            }
            $this->connexion->commit();
            return true;
        } catch (Exception $e) {
            $this->connexion->rollBack();
            throw $e;
        }
    }

    public function mettreAJourProfil($id, $prenom, $nom, $telephone, $cheminAvatar = null) {
        if ($cheminAvatar) {
            $stmt = $this->connexion->prepare('UPDATE users SET first_name=?, last_name=?, phone=?, avatar_path=?, updated_at=NOW() WHERE id=?');
            return $stmt->execute([$prenom, $nom, $telephone, $cheminAvatar, $id]);
        } else {
            $stmt = $this->connexion->prepare('UPDATE users SET first_name=?, last_name=?, phone=?, updated_at=NOW() WHERE id=?');
            return $stmt->execute([$prenom, $nom, $telephone, $id]);
        }
    }

    public function supprimerCompte($id) {
        return $this->connexion->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
    }

    public function creerTokenVerificationEmail($userId, $token) {
        return $this->connexion->prepare('INSERT INTO email_verifications (user_id, token, expires_at, created_at) VALUES (?,?, DATE_ADD(NOW(), INTERVAL 24 HOUR), NOW())')->execute([$userId, $token]);
    }

    public function verifierTokenEmail($token) {
        $stmt = $this->connexion->prepare('SELECT id, user_id FROM email_verifications WHERE token = ? AND used_at IS NULL AND expires_at > NOW() LIMIT 1');
        $stmt->execute([$token]);
        return $stmt->fetch();
    }

    public function validerEmail($userId, $verificationId) {
        $this->connexion->beginTransaction();
        try {
            $this->connexion->prepare('UPDATE users SET email_verified_at = NOW(), updated_at = NOW() WHERE id = ?')->execute([$userId]);
            $this->connexion->prepare('UPDATE email_verifications SET used_at = NOW() WHERE id = ?')->execute([$verificationId]);
            $this->connexion->commit();
            return true;
        } catch (Exception $e) {
            $this->connexion->rollBack();
            throw $e;
        }
    }

    public function creerCodeTelephone($userId, $code) {
        return $this->connexion->prepare('UPDATE users SET phone_code = ?, phone_code_expires_at = DATE_ADD(NOW(), INTERVAL 10 MINUTE), updated_at = NOW() WHERE id = ?')->execute([$code, $userId]);
    }

    public function verifierCodeTelephone($userId) {
        $stmt = $this->connexion->prepare('SELECT phone_code, phone_code_expires_at FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }

    public function validerTelephone($userId) {
        return $this->connexion->prepare('UPDATE users SET phone_verified_at = NOW(), phone_code = NULL, phone_code_expires_at = NULL, updated_at = NOW() WHERE id = ?')->execute([$userId]);
    }
}
