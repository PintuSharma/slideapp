<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Google\Client;
use Google\Service\Slides;
use Google\Service\Slides\Google_Service_Slides_Presentation;
use Google\Service\Slides\Google_Service_Slides_PresentationRequest;
use Google\Service\Slides\Google_Service_Slides_Slide;
use Google\Service\Slides\Google_Service_Slides_CreateSlideRequest;
use Google\Service\Slides\Google_Service_Slides_TextContent;
use Google\Service\Slides\Google_Service_Slides_ParagraphMarker;
use Google\Service\Slides\Google_Service_Slides_TextRun;
use Google\Service\Drive;

class GoogleSlidesController extends Controller
{
    public function redirectToGoogle()
    {
        // echo 'hi';exit;
        $client = $this->getClient();
        $client->setRedirectUri(route('google.callback'));
        $client->setScopes([
            'https://www.googleapis.com/auth/presentations',
            'https://www.googleapis.com/auth/drive.file', // Required for creating files in Google Drive
        ]);

        $authUrl = $client->createAuthUrl();
        return redirect()->away($authUrl);
    }

    public function handleGoogleCallback(Request $request)
    {
        $client = $this->getClient();
        $client->setRedirectUri(route('google.callback'));

        $accessToken = $client->fetchAccessTokenWithAuthCode($request->get('code'));
        $client->setAccessToken($accessToken);

        $presentation = $this->createGoogleSlide($client, $request->input('title'), $request->input('description'), $request->file('image'));

        // Get the download URL for the PowerPoint file
        $downloadUrl = $this->getDownloadUrl($presentation->presentationId, $client);

        return redirect()->back()->with('success', 'Google Slide created successfully!')->with('downloadUrl', $downloadUrl);
    }

    private function getClient()
    {
        $client = new Client();
        $client->setAuthConfig(public_path('ppt-test-393016-d030e794a31f.json'));
        $client->setAccessType('offline');

        return $client;
    }

    private function createGoogleSlide(Client $client, $title, $description, $image)
    {
        $service = new Slides($client);

        // Create a new presentation
        $presentation = new Google_Service_Slides_Presentation();
        $presentation->setTitle($title);

        $requests = [];

        // Create a slide
        $slide = new Google_Service_Slides_Slide();
        $requests[] = new Google_Service_Slides_CreateSlideRequest([
            'slideLayoutReference' => [
                'predefinedLayout' => 'TITLE_AND_BODY',
            ],
        ]);

        // Add title to the slide
        $requests[] = new Google_Service_Slides_Request([
            'insertText' => [
                'objectId' => $slide->getObjectId(),
                'text' => $title,
                'insertionIndex' => 0,
            ],
        ]);

        // Add description to the slide
        $requests[] = new Google_Service_Slides_Request([
            'insertText' => [
                'objectId' => $slide->getObjectId(),
                'text' => $description,
                'insertionIndex' => strlen($title) + 1, // Add description after the title
            ],
        ]);

        // Add image to the slide
        if ($image) {
            $imageContent = file_get_contents($image->getRealPath());
            $driveService = new Drive($client);
            $driveFile = new \Google_Service_Drive_DriveFile();
            $driveFile->setName('image.jpg');
            $imageFile = $driveService->files->create($driveFile, [
                'data' => $imageContent,
                'mimeType' => 'image/jpeg',
                'uploadType' => 'multipart',
                'fields' => 'id',
            ]);

            $requests[] = new Google_Service_Slides_Request([
                'createImage' => [
                    'objectId' => $slide->getObjectId(),
                    'url' => $imageFile->id,
                    'elementProperties' => [
                        'pageObjectId' => $slide->getObjectId(),
                    ],
                ],
            ]);
        }

        $presentationRequest = new Google_Service_Slides_PresentationRequest([
            'requests' => $requests,
        ]);

        $response = $service->presentations->batchUpdate($presentation->presentationId, $presentationRequest);

        return $presentation;
    }

    private function getDownloadUrl($presentationId, Client $client)
    {
        $driveService = new Drive($client);

        // Export the presentation to a PowerPoint file
        $exportRequest = $driveService->files->export($presentationId, 'application/vnd.openxmlformats-officedocument.presentationml.presentation', ['alt' => 'media']);

        // Generate a temporary download URL for the exported file
        $exportUrl = $exportRequest->getMediaLink();

        return $exportUrl;
    }
}
