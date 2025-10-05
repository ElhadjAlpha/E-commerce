<?php

namespace App\Controller;

use App\Entity\Category;
use App\Form\CategoryFormType;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/category', name: 'category_')]
final class CategoryController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CategoryRepository $categoryRepository
    ) {}

    #[Route('/list', name: 'list')]
    public function index(): Response
    {
        $categories = $this->categoryRepository->findAll();

        return $this->render('category/index.html.twig', [
            'categories' => $categories,
        ]);
    }

    #[Route('/new', name: 'new')]
    public function new(Request $request): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryFormType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $existingCategory = $this->categoryRepository->findOneBy([
                'name' => $category->getName()
            ]);

            if ($existingCategory) {
                $this->addFlash('error', 'Une catégorie avec ce nom existe déjà.');
            } else {
                $this->entityManager->persist($category);
                $this->entityManager->flush();
                $this->addFlash('success', 'Catégorie créée avec succès !');

                return $this->redirectToRoute('category_list');
            }
        }

        return $this->render('category/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'detail', requirements: ['id' => '\d+'])]
    public function detail(Category $category): Response
    {
        return $this->render('category/detail.html.twig', [
            'category' => $category,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', requirements: ['id' => '\d+'])]
    public function update(Request $request, Category $category): Response
    {
        $form = $this->createForm(CategoryFormType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $existingCategory = $this->categoryRepository->findOneBy([
                'name' => $category->getName()
            ]);

            if ($existingCategory && $existingCategory->getId() !== $category->getId()) {
                $this->addFlash('error', 'Une autre catégorie possède déjà ce nom.');
            } else {
                $this->entityManager->flush();
                $this->addFlash('success', 'Catégorie mise à jour avec succès !');

                return $this->redirectToRoute('category_list');
            }
        }

        return $this->render('category/edit.html.twig', [
            'form' => $form->createView(),
            'category' => $category,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, Category $category): Response
    {
        // Vérifier le token CSRF pour sécuriser la suppression
        if ($this->isCsrfTokenValid('delete_category_' . $category->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($category);
            $this->entityManager->flush();
            $this->addFlash('success', 'Catégorie supprimée avec succès !');
        } else {
            $this->addFlash('error', 'Token CSRF invalide, suppression annulée.');
        }

        return $this->redirectToRoute('category_list');
    }
}
