<?php

namespace App\Controller;

use DateTime;
use App\Form\ChangePasswordFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @Route("/user")
 */
class SecurityController extends AbstractController
{
    /**
     * @Route("/login", name="app_login")
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {

            $this->addFlash('warning', 'Vous êtes deja connecté.');
            return $this->redirectToRoute('default_home');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout(): void
    {
        // throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }



    /**
     * @Route("/profile/changer-mon -mot-de-passe", name="change_user_password", methods={"GET|POST"})
     */
    public function changeUserPassword(
        EntityManagerInterface $entityManager,
        Request $request,
        UserPasswordHasherInterface
        $passwordHasher
    ): Response {
        $form = $this->createForm(ChangePasswordFormType::class)->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $user = $this->getUser();

            $user->setPassword($passwordHasher->hashPassword($user, $form->get('plainPassword')->getData()));
            $user->setUpdatedAt(new DateTime());

            $entityManager->persist($user);
            $entityManager->flush();


            $this->addFlash('success', 'Mot de passe modifié avec succès.');
            return $this->redirectToRoute('show_profile');
        }


        return $this->render('security/change_password.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
