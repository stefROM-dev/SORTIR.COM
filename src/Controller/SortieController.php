<?php

namespace App\Controller;

use App\Entity\Etat;
use App\Entity\Sortie;
use App\Form\SortieType;
use App\Repository\SortieRepository;
use App\Service\SortieService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class SortieController extends AbstractController
{
    private $repository;
    private $manager;
    private $service;

    public function __construct(EntityManagerInterface $manager, SortieRepository $repository, SortieService $service)
    {
        $this->repository = $repository;
        $this->manager = $manager;
        $this->service = $service;
    }

    /**
     * @Route("/sortie/{id}", name="sortie_display", requirements={"id": "\d+"})
     * @param int $id
     * @return Response
     */
    public function display(int $id)
    {
        $sortie = $this->repository->find($id);
        if ($sortie != null) {
            return $this->render('sortie/display.html.twig', ["sortie" => $sortie]);
        } else {
            return $this->redirectToRoute("sorties_display");
        }
    }

    /**
     * @Route("/sortie/{id}/persist", name="sortie_persist", requirements={"id": "\d+"}, defaults={"id": 0})
     * @param int $id
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function persist(int $id, Request $request)
    {
        // Checking if the already entity exists, is still available to update, and was made by the current user.
        // Redirecting to different routes if one is not the case.
        $sortie = new Sortie();
        if ($id) {
            $sortie = $this->repository->find($id);
            if ($sortie && !$this->service->isEditable($sortie, $this->getUser())) {
                return $this->redirectToRoute("sortie_display", ["id" => $sortie->getId()]);
            }
        }
        if (!$sortie) {
            return $this->redirectToRoute("sortie_persist");
        }

        // Creating the form and handling the request.
        $sortieForm = $this->createForm(SortieType::class, $sortie);
        $sortieForm->handleRequest($request);

        // Handling the form submission.
        if ($sortieForm->isSubmitted() && $sortieForm->isValid()) {
            //Setting the fields "organisateur" and "siteOrganisateur":
            $organisateur = $this->getUser();
            $campus = $organisateur->getCampus();
            $sortie->setOrganisateur($organisateur);
            $sortie->setSiteOrganisateur($campus);

            // Setting the field "etat" according to the submit input which was clicked on:
            $redirection = "sortie_display";
            $status = 1;
            if ($sortieForm->get('save')->isClicked()) {
                $redirection = "sortie_persist";
            } else if ($sortieForm->get('publish')->isClicked()) {
                $status = 2;
            } else if ($sortieForm->get('delete')->isClicked()) {
                $status = 6;
            }
            $this->service->setEtat($sortie, $status, $this->getDoctrine()->getRepository(Etat::class));

            // Persisting the entity.
            $this->manager->persist($sortie);
            $this->manager->flush();

            // Redirecting.
            return $this->redirectToRoute($redirection, ["id" => $sortie->getId()]);
        }

        // Redirecting if the form is not submitted.
        return $this->render("sortie/persist.html.twig", ["sortieForm" => $sortieForm->createView(), "sortie" => $sortie]);
    }

    /**
     * @Route("/sorties/api", name="sorties_list", methods={"GET"})
     * @param Request $request
     * @param SerializerInterface $serializer
     * @return Response
     */
    public function list(Request $request, SerializerInterface $serializer)
    {
        $participant = $request->get("participant");
        $nom = $request->get("nom") ? $request->get("nom") : "";
        $campus = $request->get("campus");
        $from = $request->get("from");
        $to = $request->get("to");
        $isOrganisateur = $request->get("isOrganisateur");
        $isInscrit = $request->get("isInscrit");
        $isNotInscrit = $request->get("isNotInscrit");
        $isFinie = $request->get("isFinie");
        $sorties = $this->repository->findByParameters($participant, $nom, $campus, $from, $to, $isOrganisateur, $isInscrit, $isNotInscrit, $isFinie);
        // Returning a JSON Response.
        $response = new Response();
        $response->headers->set("Content-Type", "application/json");
        $json = $serializer->serialize($sorties, "json", ["groups" => "sortie"]);
        $response->setContent($json);
        return $response;
    }

    /**
     * @Route("/", name="sorties_display")
     * @param Request $request
     * @return Response
     */
    public function home(Request $request)
    {
        return $this->render("sortie/sorties.html.twig");
    }

    /**
     *
     */
    public function subscribe()
    {

    }

    /**
     *
     */
    public function unsubscribe()
    {

    }

}
