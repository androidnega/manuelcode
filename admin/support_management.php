<?php
include 'auth/check_auth.php';
include '../includes/db.php';

$admin_username = $_SESSION['admin_name'] ?? 'Admin';
$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_agent':
                $name = trim($_POST['name']);
                $email = trim($_POST['email']);
                $phone = trim($_POST['phone']);
                $department = trim($_POST['department']);
                $password = $_POST['password'];
                
                if (empty($name) || empty($email) || empty($password)) {
                    $error_message = 'Name, email, and password are required.';
                } else {
                    try {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("
                            INSERT INTO support_agents (name, email, phone, department, password, created_at)
                            VALUES (?, ?, ?, ?, ?, NOW())
                        ");
                        $stmt->execute([$name, $email, $phone, $department, $hashed_password]);
                        $success_message = 'Support agent created successfully!';
                    } catch (Exception $e) {
                        $error_message = 'Error creating support agent: ' . $e->getMessage();
                    }
                }
                break;
                
                         case 'update_agent':
                 $agent_id = (int)$_POST['agent_id'];
                 $name = trim($_POST['name']);
                 $email = trim($_POST['email']);
                 $phone = trim($_POST['phone']);
                 $department = trim($_POST['department']);
                 $status = isset($_POST['is_active']) ? 'active' : 'inactive';
                 
                 try {
                     $stmt = $pdo->prepare("
                         UPDATE support_agents 
                         SET name = ?, email = ?, phone = ?, department = ?, status = ?
                         WHERE id = ?
                     ");
                     $stmt->execute([$name, $email, $phone, $department, $status, $agent_id]);
                     $success_message = 'Support agent updated successfully!';
                 } catch (Exception $e) {
                     $error_message = 'Error updating support agent: ' . $e->getMessage();
                 }
                 break;
                
            case 'delete_agent':
                $agent_id = (int)$_POST['agent_id'];
                try {
                    $stmt = $pdo->prepare("DELETE FROM support_agents WHERE id = ?");
                    $stmt->execute([$agent_id]);
                    $success_message = 'Support agent deleted successfully!';
                } catch (Exception $e) {
                    $error_message = 'Error deleting support agent: ' . $e->getMessage();
                }
                break;
        }
    }
}

// Get support agents
$stmt = $pdo->query("SELECT * FROM support_agents ORDER BY created_at DESC");
$support_agents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get support tickets statistics
$stmt = $pdo->query("SELECT COUNT(*) as total FROM support_tickets");
$total_tickets = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->query("SELECT COUNT(*) as open FROM support_tickets WHERE status = 'open'");
$open_tickets = $stmt->fetch(PDO::FETCH_ASSOC)['open'];

$stmt = $pdo->query("SELECT COUNT(*) as closed FROM support_tickets WHERE status = 'closed'");
$closed_tickets = $stmt->fetch(PDO::FETCH_ASSOC)['closed'];

$stmt = $pdo->query("SELECT COUNT(*) as active FROM support_agents WHERE status = 'active'");
$active_agents = $stmt->fetch(PDO::FETCH_ASSOC)['active'];
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Support Management - Admin</title>
  <link rel="icon" type="image/svg+xml" href="../assets/favi/login-favicon.svg">
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <style>
    /* Global mobile responsiveness fixes */
    body {
      overflow-x: hidden;
      max-width: 100vw;
    }
    
    .sidebar-transition {
      transition: transform 0.3s ease-in-out;
    }
    .mobile-overlay {
      transition: opacity 0.3s ease-in-out;
    }
    .scrollbar-hide {
      -ms-overflow-style: none;
      scrollbar-width: none;
    }
    .scrollbar-hide::-webkit-scrollbar {
      display: none;
    }
    
         @media (max-width: 768px) {
       .main-content {
         padding: 1rem;
         width: 100%;
         max-width: 100%;
         overflow-x: hidden;
       }
       .mobile-header {
         padding: 1rem;
         position: sticky;
         top: 0;
         z-index: 30;
         background: white;
         border-bottom: 1px solid #e5e7eb;
       }
       .mobile-title {
         font-size: 1.25rem;
         font-weight: 600;
       }
       .table-responsive {
         font-size: 0.875rem;
         overflow-x: auto;
         -webkit-overflow-scrolling: touch;
         min-width: 600px;
       }
       .table-responsive th,
       .table-responsive td {
         padding: 0.5rem 0.25rem;
         white-space: nowrap;
       }
       .grid {
         grid-template-columns: 1fr;
         gap: 1rem;
       }
       .card {
         width: 100%;
         max-width: 100%;
       }
     }
     
     /* Make table more responsive */
     .table-responsive {
       overflow-x: auto;
       -webkit-overflow-scrolling: touch;
     }
     
     /* Ensure no horizontal scroll on larger screens */
     @media (min-width: 1024px) {
       .table-responsive {
         overflow-x: visible;
       }
     }
  </style>
</head>
<body class="<?php echo $dark_mode ? 'bg-gray-900 dark' : 'bg-gray-50'; ?>">
  <?php include 'includes/sidebar.php'; ?>
  
  <main class="main-content p-6">
         <!-- Success/Error Messages -->
     <?php if ($success_message): ?>
       <div class="<?php echo $dark_mode ? 'bg-green-900 border-green-700 text-green-200' : 'bg-green-50 border-green-200 text-green-700'; ?> px-4 py-3 rounded-lg mb-6 border">
         <div class="flex">
           <i class="fas fa-check-circle mt-1 mr-3"></i>
           <p><?php echo htmlspecialchars($success_message); ?></p>
         </div>
       </div>
     <?php endif; ?>

     <?php if ($error_message): ?>
       <div class="<?php echo $dark_mode ? 'bg-red-900 border-red-700 text-red-200' : 'bg-red-50 border-red-200 text-red-700'; ?> px-4 py-3 rounded-lg mb-6 border">
         <div class="flex">
           <i class="fas fa-exclamation-circle mt-1 mr-3"></i>
           <p><?php echo htmlspecialchars($error_message); ?></p>
         </div>
       </div>
     <?php endif; ?>

         <!-- Statistics Cards -->
     <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
       <div class="<?php echo $dark_mode ? 'bg-gray-800 border-gray-600' : 'bg-white border-gray-200'; ?> rounded-lg shadow-sm border p-6">
         <div class="flex items-center">
           <div class="p-3 bg-blue-100 rounded-lg">
             <i class="fas fa-headset text-blue-600 text-xl"></i>
           </div>
           <div class="ml-4">
             <p class="text-sm font-medium <?php echo $dark_mode ? 'text-gray-300' : 'text-gray-600'; ?>">Total Agents</p>
             <p class="text-2xl font-bold <?php echo $dark_mode ? 'text-white' : 'text-gray-900'; ?>"><?php echo count($support_agents); ?></p>
           </div>
         </div>
       </div>
      
             <div class="<?php echo $dark_mode ? 'bg-gray-800 border-gray-600' : 'bg-white border-gray-200'; ?> rounded-lg shadow-sm border p-6">
         <div class="flex items-center">
           <div class="p-3 bg-green-100 rounded-lg">
             <i class="fas fa-user-check text-green-600 text-xl"></i>
           </div>
           <div class="ml-4">
             <p class="text-sm font-medium <?php echo $dark_mode ? 'text-gray-300' : 'text-gray-600'; ?>">Active Agents</p>
             <p class="text-2xl font-bold <?php echo $dark_mode ? 'text-white' : 'text-gray-900'; ?>"><?php echo $active_agents; ?></p>
           </div>
         </div>
       </div>
      
             <div class="<?php echo $dark_mode ? 'bg-gray-800 border-gray-600' : 'bg-white border-gray-200'; ?> rounded-lg shadow-sm border p-6">
         <div class="flex items-center">
           <div class="p-3 bg-yellow-100 rounded-lg">
             <i class="fas fa-ticket-alt text-yellow-600 text-xl"></i>
           </div>
           <div class="ml-4">
             <p class="text-sm font-medium <?php echo $dark_mode ? 'text-gray-300' : 'text-gray-600'; ?>">Open Tickets</p>
             <p class="text-2xl font-bold <?php echo $dark_mode ? 'text-white' : 'text-gray-900'; ?>"><?php echo $open_tickets; ?></p>
           </div>
         </div>
       </div>
       
       <div class="<?php echo $dark_mode ? 'bg-gray-800 border-gray-600' : 'bg-white border-gray-200'; ?> rounded-lg shadow-sm border p-6">
         <div class="flex items-center">
           <div class="p-3 bg-purple-100 rounded-lg">
             <i class="fas fa-clipboard-check text-purple-600 text-xl"></i>
           </div>
           <div class="ml-4">
             <p class="text-sm font-medium <?php echo $dark_mode ? 'text-gray-300' : 'text-gray-600'; ?>">Total Tickets</p>
             <p class="text-2xl font-bold <?php echo $dark_mode ? 'text-white' : 'text-gray-900'; ?>"><?php echo $total_tickets; ?></p>
           </div>
         </div>
       </div>
    </div>

         <!-- Create Support Agent -->
     <div class="<?php echo $dark_mode ? 'bg-gray-800 border-gray-600' : 'bg-white border-gray-200'; ?> rounded-lg shadow-sm border mb-6">
       <div class="px-6 py-4 border-b <?php echo $dark_mode ? 'border-gray-600' : 'border-gray-200'; ?>">
         <h2 class="text-lg font-semibold <?php echo $dark_mode ? 'text-white' : 'text-gray-800'; ?>">Create Support Agent</h2>
       </div>
      <div class="p-6">
        <form method="POST" class="space-y-4">
          <input type="hidden" name="action" value="create_agent">
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                         <div>
               <label for="name" class="block text-sm font-medium <?php echo $dark_mode ? 'text-gray-200' : 'text-gray-700'; ?> mb-2">Name *</label>
               <input type="text" id="name" name="name" required
                      class="w-full px-3 py-2 border <?php echo $dark_mode ? 'border-gray-600 bg-gray-700 text-white' : 'border-gray-300'; ?> rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
             </div>
            
                         <div>
               <label for="email" class="block text-sm font-medium <?php echo $dark_mode ? 'text-gray-200' : 'text-gray-700'; ?> mb-2">Email *</label>
               <input type="email" id="email" name="email" required
                      class="w-full px-3 py-2 border <?php echo $dark_mode ? 'border-gray-600 bg-gray-700 text-white' : 'border-gray-300'; ?> rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
             </div>
             
             <div>
               <label for="phone" class="block text-sm font-medium <?php echo $dark_mode ? 'text-gray-200' : 'text-gray-700'; ?> mb-2">Phone</label>
               <input type="tel" id="phone" name="phone"
                      class="w-full px-3 py-2 border <?php echo $dark_mode ? 'border-gray-600 bg-gray-700 text-white' : 'border-gray-300'; ?> rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
             </div>
             
             <div>
               <label for="department" class="block text-sm font-medium <?php echo $dark_mode ? 'text-gray-200' : 'text-gray-700'; ?> mb-2">Department</label>
               <select id="department" name="department" 
                       class="w-full px-3 py-2 border <?php echo $dark_mode ? 'border-gray-600 bg-gray-700 text-white' : 'border-gray-300'; ?> rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                 <option value="General">General Support</option>
                 <option value="Technical">Technical Support</option>
                 <option value="Billing">Billing Support</option>
                 <option value="Sales">Sales Support</option>
               </select>
             </div>
             
             <div>
               <label for="password" class="block text-sm font-medium <?php echo $dark_mode ? 'text-gray-200' : 'text-gray-700'; ?> mb-2">Password *</label>
               <input type="password" id="password" name="password" required
                      class="w-full px-3 py-2 border <?php echo $dark_mode ? 'border-gray-600 bg-gray-700 text-white' : 'border-gray-300'; ?> rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
             </div>
          </div>
          
          <div class="flex justify-end">
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition-colors">
              <i class="fas fa-plus mr-2"></i>Create Agent
            </button>
          </div>
        </form>
      </div>
    </div>

         <!-- Support Agents List -->
     <div class="<?php echo $dark_mode ? 'bg-gray-800 border-gray-600' : 'bg-white border-gray-200'; ?> rounded-lg shadow-sm border">
       <div class="px-6 py-4 border-b <?php echo $dark_mode ? 'border-gray-600' : 'border-gray-200'; ?>">
         <h2 class="text-lg font-semibold <?php echo $dark_mode ? 'text-white' : 'text-gray-800'; ?>">Support Agents</h2>
       </div>
      <div class="p-6">
                 <?php if (empty($support_agents)): ?>
           <div class="text-center py-8">
             <i class="fas fa-headset text-4xl <?php echo $dark_mode ? 'text-gray-500' : 'text-gray-300'; ?> mb-4"></i>
             <p class="<?php echo $dark_mode ? 'text-gray-300' : 'text-gray-600'; ?>">No support agents found</p>
             <p class="text-sm <?php echo $dark_mode ? 'text-gray-400' : 'text-gray-500'; ?>">Create your first support agent to get started.</p>
           </div>
        <?php else: ?>
                     <div class="overflow-x-auto table-responsive">
             <table class="w-full min-w-full">
               <thead>
                 <tr class="border-b <?php echo $dark_mode ? 'border-gray-600' : 'border-gray-200'; ?>">
                   <th class="text-left py-3 px-2 font-medium <?php echo $dark_mode ? 'text-gray-200' : 'text-gray-700'; ?> text-sm">Agent</th>
                   <th class="text-left py-3 px-2 font-medium <?php echo $dark_mode ? 'text-gray-200' : 'text-gray-700'; ?> text-sm">Contact</th>
                   <th class="text-left py-3 px-2 font-medium <?php echo $dark_mode ? 'text-gray-200' : 'text-gray-700'; ?> text-sm">Department</th>
                   <th class="text-left py-3 px-2 font-medium <?php echo $dark_mode ? 'text-gray-200' : 'text-gray-700'; ?> text-sm">Status</th>
                   <th class="text-left py-3 px-2 font-medium <?php echo $dark_mode ? 'text-gray-200' : 'text-gray-700'; ?> text-sm">Created</th>
                   <th class="text-left py-3 px-2 font-medium <?php echo $dark_mode ? 'text-gray-200' : 'text-gray-700'; ?> text-sm">Actions</th>
                 </tr>
               </thead>
              <tbody>
                <?php foreach ($support_agents as $agent): ?>
                                     <tr class="border-b <?php echo $dark_mode ? 'border-gray-700 hover:bg-gray-700' : 'border-gray-100 hover:bg-gray-50'; ?>">
                     <td class="py-3 px-2">
                       <div>
                         <div class="font-medium <?php echo $dark_mode ? 'text-white' : 'text-gray-900'; ?> text-sm"><?php echo htmlspecialchars($agent['name']); ?></div>
                         <div class="text-xs <?php echo $dark_mode ? 'text-gray-400' : 'text-gray-500'; ?>">ID: <?php echo $agent['id']; ?></div>
                       </div>
                     </td>
                     <td class="py-3 px-2">
                       <div>
                         <div class="text-sm <?php echo $dark_mode ? 'text-white' : 'text-gray-900'; ?>"><?php echo htmlspecialchars($agent['email']); ?></div>
                         <?php if ($agent['phone']): ?>
                           <div class="text-xs <?php echo $dark_mode ? 'text-gray-400' : 'text-gray-500'; ?>"><?php echo htmlspecialchars($agent['phone']); ?></div>
                         <?php endif; ?>
                       </div>
                     </td>
                     <td class="py-3 px-2">
                       <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                         <?php echo htmlspecialchars($agent['department']); ?>
                       </span>
                     </td>
                     <td class="py-3 px-2">
                       <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $agent['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                         <?php echo ucfirst($agent['status']); ?>
                       </span>
                     </td>
                     <td class="py-3 px-2 text-xs text-gray-500">
                       <?php echo date('M j, Y', strtotime($agent['created_at'])); ?>
                     </td>
                     <td class="py-3 px-2">
                       <div class="flex space-x-2">
                         <button onclick="editAgent(<?php echo $agent['id']; ?>)" 
                                 class="text-blue-600 hover:text-blue-800">
                           <i class="fas fa-edit"></i>
                         </button>
                         <button onclick="deleteAgent(<?php echo $agent['id']; ?>)" 
                                 class="text-red-600 hover:text-red-800">
                           <i class="fas fa-trash"></i>
                         </button>
                       </div>
                     </td>
                   </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </main>
  
  <!-- Edit Agent Modal -->
  <div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-semibold text-gray-800">Edit Support Agent</h3>
        </div>
        <form id="editForm" method="POST" class="p-6">
          <input type="hidden" name="action" value="update_agent">
          <input type="hidden" name="agent_id" id="edit_agent_id">
          
          <div class="space-y-4">
            <div>
              <label for="edit_name" class="block text-sm font-medium text-gray-700 mb-2">Name</label>
              <input type="text" id="edit_name" name="name" required
                     class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            
            <div>
              <label for="edit_email" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
              <input type="email" id="edit_email" name="email" required
                     class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            
            <div>
              <label for="edit_phone" class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
              <input type="tel" id="edit_phone" name="phone"
                     class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            
            <div>
              <label for="edit_department" class="block text-sm font-medium text-gray-700 mb-2">Department</label>
              <select id="edit_department" name="department" 
                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="General">General Support</option>
                <option value="Technical">Technical Support</option>
                <option value="Billing">Billing Support</option>
                <option value="Sales">Sales Support</option>
              </select>
            </div>
            
            <div class="flex items-center">
              <input type="checkbox" id="edit_is_active" name="is_active" class="mr-2">
              <label for="edit_is_active" class="text-sm font-medium text-gray-700">Active</label>
            </div>
          </div>
          
          <div class="flex justify-end space-x-3 mt-6">
            <button type="button" onclick="closeEditModal()" 
                    class="px-4 py-2 text-gray-600 hover:text-gray-800 transition-colors">
              Cancel
            </button>
            <button type="submit" 
                    class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
              Update Agent
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
  
  <script>
    function editAgent(agentId) {
      // Fetch agent data and populate modal
      fetch(`get_support_agent.php?id=${agentId}`)
        .then(response => response.json())
        .then(data => {
          document.getElementById('edit_agent_id').value = data.id;
          document.getElementById('edit_name').value = data.name;
          document.getElementById('edit_email').value = data.email;
          document.getElementById('edit_phone').value = data.phone || '';
          document.getElementById('edit_department').value = data.department;
                     document.getElementById('edit_is_active').checked = data.status === 'active';
          
          document.getElementById('editModal').classList.remove('hidden');
        })
        .catch(error => console.error('Error:', error));
    }
    
    function closeEditModal() {
      document.getElementById('editModal').classList.add('hidden');
    }
    
    function deleteAgent(agentId) {
      if (confirm('Are you sure you want to delete this support agent? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
          <input type="hidden" name="action" value="delete_agent">
          <input type="hidden" name="agent_id" value="${agentId}">
        `;
        document.body.appendChild(form);
        form.submit();
      }
    }
    
    // Close modal when clicking outside
    document.getElementById('editModal').addEventListener('click', function(e) {
      if (e.target === this) {
        closeEditModal();
      }
    });
  </script>
  
  <?php include 'includes/footer.php'; ?>
</body>
</html>
