<?php

namespace App\Controller;

use App\Entity\Invoice;
use App\Services\FileUploaderService;
use App\Services\InvoicesService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route ("/api")
 */
class InvoicesController extends AbstractController
{
    /**
     * @var InvoicesService
     */
    private InvoicesService $invoicesService;

    /**
     * @var FileUploaderService
     */
    private FileUploaderService $fileUploader;

    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;

    /**
     * @param InvoicesService $invoicesService
     * @param FileUploaderService $fileUploader
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(InvoicesService $invoicesService, FileUploaderService $fileUploader, EntityManagerInterface $entityManager)
    {
        $this->invoicesService = $invoicesService;
        $this->entityManager = $entityManager;
        $this->fileUploader = $fileUploader;
    }

    /**
     * @Route("/invoices", name="add-invoice", methods={"POST"})
     * @return Response
     */
    public function store(Request $request): Response
    {
        $requestBody = $request->request->all();

        $requiredParameters = [
            'creditor_id' => 'creditor id is required',
            'debtor_id' => 'debtor id is required',
            'price' => 'price is required',
        ];

        $invoiceDocument = $request->files->get("document");

        $errors = $this->validateRequest($requiredParameters, $requestBody, $invoiceDocument);
        if (count($errors) > 0) {
            return $this->json($errors, 422);
        }

        try {
            $requestBody["document"] = $this->fileUploader->upload("/invoices", $invoiceDocument);
        } catch (\Exception $e) {
            return $this->json($this->errorResponse($e->getMessage()), $e->getCode());
        }
        $invoice = $this->invoicesService->createInvoice($requestBody);

        if (gettype($invoice) == "string") {
            return $this->json($this->errorResponse($invoice), 422);
        }

        return $this->json([
            "response" => [
                "message" => "Invoice Created",
                "data" => [$invoice]
            ]
        ]);
    }


    /**
     * @Route("/invoices/{invoiceId}/pay", name="pay-invoice", methods={"GET"}, requirements={"invoice"="\d+"})
     * @return Response
     */
    public function pay($invoiceId): Response
    {
        $invoice = $this->entityManager->getRepository(Invoice::class)->find($invoiceId);

        if (!$invoice || $invoice->getStatus() != Invoice::PENDING) {
            return $this->json($this->errorResponse("Sorry not valid invoice to pay!"), 422);
        }


        $invoice = $this->invoicesService->payInvoice($invoice);

        if (gettype($invoice) == "string") {
            return $this->json($this->errorResponse($invoice), 422);
        }

        return $this->json([
            "response" => [
                "message" => "Invoice Paid Successfully",
                "data" => [$invoice]
            ]
        ]);
    }

    /**
     * @param $requiredParameters
     * @param $requestBody
     * @return array|array[]
     */
    private function validateRequest($requiredParameters, $requestBody, UploadedFile|null $invoiceDocument): array
    {
        foreach ($requiredParameters as $parameter => $errorMessage) {
            if (!isset($requestBody[$parameter])) {
                return $this->errorResponse($errorMessage);
            }
        }
        if ($requestBody["creditor_id"] == $requestBody["debtor_id"]) {
            return $this->errorResponse("debtor and creditor can't be the same");
        }
        //of course the threshold in this business will not be zero, it will be much larger
        if ($requestBody["price"] <= 0) {
            return $this->errorResponse("price must be greater than 0");
        }

        if (!$invoiceDocument) {
            return $this->errorResponse("invoice document is required");
        }
        if (!in_array($invoiceDocument->getClientOriginalExtension(), ["pdf", "csv", "xls"])) {
            return $this->errorResponse("Uploaded document has not valid file type (pdf,xls or csv)");
        }

        return [];
    }

    /**
     * @param $errorMessage
     * @return array[]
     */
    private function errorResponse($errorMessage): array
    {
        return ["response" => ["message" => $errorMessage]];
    }
}
