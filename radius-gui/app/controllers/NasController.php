<?php
/**
 * NAS Controller
 *
 * Manages Network Access Servers (NAS) - Access Points, WiFi Controllers, etc.
 */

require_once APP_PATH . '/models/Nas.php';

class NasController
{
    private $nasModel;

    public function __construct()
    {
        $this->nasModel = new Nas();

        // Check if user is logged in
        if (!Auth::check()) {
            header('Location: index.php?page=login');
            exit;
        }
    }

    /**
     * List all NAS devices
     */
    public function indexAction()
    {
        $nasDevices = $this->nasModel->getAll();
        $totalCount = $this->nasModel->getTotalCount();

        // Get statistics for each NAS
        foreach ($nasDevices as &$nas) {
            $nas['stats'] = $this->nasModel->getActivityStats($nas['nasname'], 7);
        }

        require_once APP_PATH . '/views/layouts/header.php';
        require_once APP_PATH . '/views/nas/index.php';
        require_once APP_PATH . '/views/layouts/footer.php';
    }

    /**
     * Show NAS details
     */
    public function viewAction()
    {
        $id = Utils::get('id');

        if (!$id) {
            Utils::redirect('nas', 'index');
            return;
        }

        $nas = $this->nasModel->getById($id);

        if (!$nas) {
            $_SESSION['error'] = 'NAS device not found';
            Utils::redirect('nas', 'index');
            return;
        }

        // Get detailed statistics
        $stats = $this->nasModel->getActivityStats($nas['nasname'], 30);

        require_once APP_PATH . '/views/layouts/header.php';
        require_once APP_PATH . '/views/nas/view.php';
        require_once APP_PATH . '/views/layouts/footer.php';
    }

    /**
     * Show create NAS form
     */
    public function createAction()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleCreate();
            return;
        }

        require_once APP_PATH . '/views/layouts/header.php';
        require_once APP_PATH . '/views/nas/create.php';
        require_once APP_PATH . '/views/layouts/footer.php';
    }

    /**
     * Handle NAS creation
     */
    private function handleCreate()
    {
        $nasname = trim($_POST['nasname'] ?? '');
        $shortname = trim($_POST['shortname'] ?? '');
        $type = trim($_POST['type'] ?? 'other');
        $secret = trim($_POST['secret'] ?? '');
        $description = trim($_POST['description'] ?? '');

        // Validation
        if (empty($nasname)) {
            $_SESSION['error'] = 'NAS IP address or hostname is required';
            Utils::redirect('nas', 'create');
            return;
        }

        if (empty($shortname)) {
            $_SESSION['error'] = 'Short name is required';
            Utils::redirect('nas', 'create');
            return;
        }

        if (empty($secret)) {
            $_SESSION['error'] = 'RADIUS shared secret is required';
            Utils::redirect('nas', 'create');
            return;
        }

        // Check if NAS already exists
        if ($this->nasModel->exists($nasname)) {
            $_SESSION['error'] = 'NAS with this IP/hostname already exists';
            Utils::redirect('nas', 'create');
            return;
        }

        // Create NAS
        $result = $this->nasModel->create([
            'nasname' => $nasname,
            'shortname' => $shortname,
            'type' => $type,
            'secret' => $secret,
            'description' => $description
        ]);

        if ($result) {
            $_SESSION['success'] = 'NAS device created successfully';
        } else {
            $_SESSION['error'] = 'Failed to create NAS device';
        }

        Utils::redirect('nas', 'index');
    }

    /**
     * Show edit NAS form
     */
    public function editAction()
    {
        $id = Utils::get('id');

        if (!$id) {
            Utils::redirect('nas', 'index');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleEdit($id);
            return;
        }

        $nas = $this->nasModel->getById($id);

        if (!$nas) {
            $_SESSION['error'] = 'NAS device not found';
            Utils::redirect('nas', 'index');
            return;
        }

        require_once APP_PATH . '/views/layouts/header.php';
        require_once APP_PATH . '/views/nas/edit.php';
        require_once APP_PATH . '/views/layouts/footer.php';
    }

    /**
     * Handle NAS update
     */
    private function handleEdit($id)
    {
        $shortname = trim($_POST['shortname'] ?? '');
        $type = trim($_POST['type'] ?? 'other');
        $secret = trim($_POST['secret'] ?? '');
        $description = trim($_POST['description'] ?? '');

        // Validation
        if (empty($shortname)) {
            $_SESSION['error'] = 'Short name is required';
            Utils::redirect('nas', 'edit', ['id' => $id]);
            return;
        }

        if (empty($secret)) {
            $_SESSION['error'] = 'RADIUS shared secret is required';
            Utils::redirect('nas', 'edit', ['id' => $id]);
            return;
        }

        // Update NAS
        $result = $this->nasModel->update($id, [
            'shortname' => $shortname,
            'type' => $type,
            'secret' => $secret,
            'description' => $description
        ]);

        if ($result) {
            $_SESSION['success'] = 'NAS device updated successfully';
        } else {
            $_SESSION['error'] = 'Failed to update NAS device';
        }

        Utils::redirect('nas', 'index');
    }

    /**
     * Delete NAS device
     */
    public function deleteAction()
    {
        $id = Utils::get('id');

        if (!$id) {
            Utils::redirect('nas', 'index');
            return;
        }

        $result = $this->nasModel->delete($id);

        if ($result) {
            $_SESSION['success'] = 'NAS device deleted successfully';
        } else {
            $_SESSION['error'] = 'Failed to delete NAS device';
        }

        Utils::redirect('nas', 'index');
    }
}
