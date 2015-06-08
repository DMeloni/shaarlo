<?php
include_once('fct/Webshots/webshots.php');

/**
 * Retourne uniquement le chemin d'une miniature
 * 
 */
function getImgPathFromId($id) {
    $sousDossier = sprintf('%s/%s/%s', substr($id, 0, 2), substr($id, 2, 2), substr($id, 4, 2));
    $imgCapturePath = sprintf('img/capture/%s/%s.jpg', $sousDossier, $id);
    
    return $imgCapturePath;
}

/**
 * Capture une page web
 * et en fait une miniature
 * 
 * $cache : si false, on ne regarde que dans le fichier sans appel à webshot
 */
function captureUrl($url, $id, $width = 450, $height = 450, $cache = false) {
    
    $sousDossier = sprintf('%s/%s/%s', substr($id, 0, 2), substr($id, 2, 2), substr($id, 4, 2));

    $imgMiniCapturePath = sprintf('img/capture/%s/mini_%s-%s-%s.jpg', $sousDossier, $id, $width, $height);
    $imgCapturePath = sprintf('img/capture/%s/%s.jpg', $sousDossier, $id);
    
    // Dans le cas où on ne demande pas à aller faire une capture
    if(!is_file($imgMiniCapturePath) && $cache === false) {
        return '';
    }

    if (!is_dir(sprintf('img/capture/%s', $sousDossier))) {
        mkdir(sprintf('img/capture/%s', $sousDossier), 0777, true);
    }
     
    if(!is_file($imgMiniCapturePath)) {
        // Chemin de l'image de la capture du site
        $formatImgCapture = 'jpg';
        // On regarde le Content-Type de l'url
        $headersMeilleurArticleDuJour = @get_headers($url, 1);
        // Si c'est une image, on l'enregistre directement
        if (isset($headersMeilleurArticleDuJour['Content-Type'])) {
            if (strpos($headersMeilleurArticleDuJour['Content-Type'], 'image/jpeg') === 0
            && false !== ($imageMeilleurArticleDuJour = file_get_contents($url))) {
                file_put_contents($imgCapturePath, $imageMeilleurArticleDuJour);
                $formatImgCapture = 'jpeg';
            }
            if (strpos($headersMeilleurArticleDuJour['Content-Type'], 'image/png') === 0
            && false !== ($imageMeilleurArticleDuJour = file_get_contents($url))) {
                file_put_contents($imgCapturePath, $imageMeilleurArticleDuJour);
                $formatImgCapture = 'png';
            }
        }
            
        $wobj = new webshots();
        if((!is_file($imgCapturePath)) && !$wobj->url_to_image($url, $imgCapturePath)) {
            $imgCapturePath = '';
        } else {
            if (filesize($imgCapturePath) == 0) {
                return '';
            }
            // On redimensionne l'image
            $largeur = $width;
            $hauteur = $height;
            
            switch($formatImgCapture) {
                case 'png':
                    $image = imagecreatefrompng($imgCapturePath);
                break;
                case 'jpeg':
                case 'jpg':
                default:
                    $image = imagecreatefromjpeg($imgCapturePath);
                break;
            }
            $taille = getimagesize($imgCapturePath);
            $sortie = imagecreatetruecolor($largeur,$hauteur);
            $coef = min($taille[0]/$largeur,$taille[1]/$hauteur);
             
            $deltax = $taille[0]-($coef * $largeur); 
            $deltay = $taille[1]-($coef * $hauteur);
             
            //imagecopyresampled($sortie,$image,0,0,$deltax/2,$deltay/2,$largeur,$hauteur,$taille[0]-$deltax,$taille[1]-$deltay);
            imagecopyresampled($sortie,$image,0,0,0,0,$largeur,$hauteur,$taille[0]-$deltax,$taille[1]-$deltay);

            // Jpeg progressif
            imageinterlace($sortie, 1);
            
            imagejpeg($sortie, $imgMiniCapturePath, 100);
        }
    }

    if(!is_file($imgMiniCapturePath) 
    || filesize($imgMiniCapturePath) == 1367 
    || filesize($imgMiniCapturePath) == 1850  
    
    || filesize($imgMiniCapturePath) == 838) {
        $imgMiniCapturePath = '';
    }

    // Suppression image $imgCapturePath pour faire de la place
   // unlink($imgCapturePath);

    return $imgMiniCapturePath;
}

