<?php

namespace App\Controller;

use App\Entity\Article;
use App\Form\ArticleFormType;
use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route("/admin")]
class AdminController extends AbstractController
{
    #[Route("/tableau-de-bord", name: "show_dashboard", methods: ["GET"])]
    public function showdashboad(EntityManagerInterface $entityManager): Response
    {

        $articles = $entityManager->getRepository(Article::class)->findAll();

        return $this->render('admin/show_dashboard.html.twig', [
            'articles' => $articles,
        ]);
    }

    #[Route("/creer-un-article", name: "create_article", methods: ["GET|POST"])]
    public function createArticle(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $article = new Article();

        $form = $this->createForm(ArticleFormType::class, $article)->handleRequest($request);


        //  traitement formulaire
        if ($form->isSubmitted() && $form->isValid()) {

            //  Pour accéder à une valeur d'un input de $ form on fait ;
            //  $form get('title')->getData(

            $article->setAlias($slugger->slug($article->getTitle()));
            $article->setCreatedAt(new DateTime());
            $article->setUpdatedAt(new DateTime());

            // Variabilisation du fichié 'photo' uploadé.
            $file = $form->get('photo')->getdata();
            //  if (isset($file) === true)
            //  si un fichier est uploadé (depuis le formulaire)
            if ($file) {

                // Maintenant il s'agit de reconstruire le nom du fichier pour le sécuriser.

                //  1ère étape : on déconstruit le nom du fichier et on variabilise.
                $extension = '.' . $file->guessExtension();
                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

                // Assainissement du nom de fichier(du filename)
                // $safeFilename = $slugger->slug($originalFilename);
                $safeFilename = $article->getalias();

                // 2ème étape : on reconstruit le nom du fichier maintenant quil est safe.
                // uniqid() est une fonction native de PHP, elle permet d'ajouter une valeur numérique (id) unique et auto-générée.
                $newFilename = $safeFilename . '_' . uniqid() . $extension;


                //  try/catch fait partie de PHP nativement.
                try {
                    // On a configuré un paramètre 'uploads_dir' dans le fichier services.yaml
                    // Ce param contient le chemin absolu de notre dossier d'upload de photo.

                    $file->move($this->getParameter('uploads_dir'), $newFilename);


                    // On set le NOM de la photo, pas le CHEMIN
                    $article->setPhoto($newFilename);
                } catch (FileException $extension) {
                }
            }

            $entityManager->persist($article);
            $entityManager->flush();

            $this->addFlash('success', 'Bravo, votre article est en ligne!');

            return $this->redirectToRoute('show_dashboard');
        }

        return $this->render('admin/form/form_article.html.twig', [
            'form' => $form->createView()
        ]);
    }



    // L'action est executée 2x et accessible par  les deux méthods ("GET|POST")
    #[Route("/modifier-un-article/{id}", name: "update_article", methods: ["GET|POST"])]
    public function updateArticle(Article $article, Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        # Condition ternaire :  $article->getPhoto() ?? ''
        # => est égal à : isset( $article->getPhoto()) ?  $article->getPhoto() : '';
        $originalPhoto = $article->getPhoto() ?? '';


        // 1er tour en méthode GET
        $form = $this->createForm(ArticleFormType::class, $article, [
            'photo' => $originalPhoto
        ])->handleRequest($request);

        // 2èeme TOUR de l'action en méthode POST
        if ($form->issubmitted() && $form->isValid()) {

            $article->setalias($slugger->slug($article->getTitle()));
            $article->setUpdatedAt(new DateTime());

            $file = $form->get('photo')->getData();


            if ($file) 
            {

                $extension = '.' . $file->guessExtension();
                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $article->getalias();
                $newFilename = $safeFilename . '_' . uniqid() . $extension;

                try {

                    $file->move($this->getParameter('uploads_dir'), $newFilename);
                    $article->setPhoto($newFilename);
                } catch (FileException $extension) {
                    # code à exécuter si une erreur est attrapée

                } // END CATCH ()

            } else {
                $article->setPhoto($originalPhoto);
            } // END if (file)


            $entityManager->persist($article);
            $entityManager->flush();

            $this->addFlash('success', "L'article" . $article->getTitle() . " à bien été modifié !");

            return $this->redirectToRoute('show_dashboard');
        } // END if($form)

        // On retourne la vue pour la méthode GET
        return $this->render('admin/form/form_article.html.twig', [
            'form' => $form->createView(),
            'article' => $article

        ]);
    }


    #[Route("/archiver-un-article/{id}", name: "soft_delete_article", methods: ["GET"])]
    public function softDeletearticle(Article $article, EntityManagerInterface $entityManager): Response
    {
        # On set la propriété deletedAt pour archiver l'article.
        # De l'autre coté on affichera les articles où deletedAt === null

        $article->setDeletedAt(new DateTime());

        $entityManager->persist($article);
        $entityManager->flush();

        $this->addFlash('success', "L'article" . $article->getTitle() . " à bien été archivé !");
        return $this->redirectToRoute('show_dashboard');
    }


    #[Route("/supprimer-un-article/{id}", name: "hard_delete_article", methods: ["GET"])]
    public function hardDeleteArticle(Article $article, EntityManagerInterface $entityManager): Response
    {

        $entityManager->remove($article);
        $entityManager->flush();
        
        $this->addFlash('success', "L'article" . $article->getTitle() . " à bien été supprimé de la base de données !");
        return $this->redirectToRoute('show_dashboard');
    }



    #[Route("/restaurer-un-article/{id}", name: "restore_article", methods: ["GET"])]
    public function restoreArticle(Article $article, EntityManagerInterface $entityManager): Response
    {


        $article->setDeletedAt();

        $entityManager->persist($article);
        $entityManager->flush();

        $this->addFlash('success', "L'article" . $article->getTitle() . " à bien été restauré!");

        return $this->redirectToRoute('show_dashboard');
    }
}
