<style>
  .user-img {
      border-radius: 50%;
      height: 25px;
      width: 25px;
      object-fit: cover;
  }
  
  .navbar-custom {
      background-color: rgb(244, 244, 244) !important;
  }

  .nav-link {
    color: #000 !important;
  }

  .notification-icon {
    position: relative;
    font-size: 18px;
  }

  .notification-badge {
    position: absolute;
    top: 5px;
    right: 5px;
    background: red;
    color: white;
    font-size: 12px;
    border-radius: 50%;
    padding: 2px 6px;
    display: none;
  }

  /* Notification Dropdown Custom Styling */
  #notif-dropdown {
  max-height: 300px; /* Adjust height if needed */
  overflow-y: auto; /* Enable scrolling */
  white-space: normal; /* Allow wrapping */
  word-wrap: break-word; /* Ensure long text wraps */
  width: 300px; /* Adjust width to prevent truncation */
}

.notif-item {
  display: block; /* Ensure full width usage */
  padding: 8px; /* Add spacing */
  font-size: 14px; /* Adjust text size */
}


.notif-item:last-child {
  border-bottom: none;
}

.notif-item:hover {
  background: #f9f9f9;
}
.notif-icon {
  font-size: 16px;
  margin-right: 10px;
  color: #28a745; /* Green color for check icon */
}

.notif-text {
  font-size: 14px;
  flex-grow: 1;
  color: #333;
}

.notif-time {
  font-size: 12px;
  color: gray;
}



</style>

<!-- Navbar -->
<nav class="main-header navbar navbar-expand navbar-custom navbar-dark">
  <ul class="navbar-nav">
    <?php if(isset($_SESSION['login_id'])): ?>
    <li class="nav-item">
      <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
    </li>
    <?php endif; ?>
  </ul>

  <ul class="navbar-nav ml-auto">
    <!-- Notification Icon -->
    <li class="nav-item dropdown">
      <a class="nav-link notification-icon" href="#" data-toggle="dropdown" id="notif-btn">
        <i class="fas fa-bell"></i>
        <span class="notification-badge" id="notif-count">0</span>
      </a>
      <div class="dropdown-menu dropdown-menu-right" id="notif-dropdown">
      </div>
    </li>

    <li class="nav-item">
      <a class="nav-link" data-widget="fullscreen" href="#" role="button">
        <i class="fas fa-expand-arrows-alt"></i>
      </a>
    </li>

    <li class="nav-item dropdown">
      <a class="nav-link" data-toggle="dropdown">
        <span>
          <div class="d-flex badge-pill">
            <span><img src="assets/uploads/<?php echo $_SESSION['login_avatar'] ?>" alt="" class="user-img border "></span>
            <span><b><?php echo ucwords($_SESSION['login_firstname']) ?></b></span>
            <span class="fa fa-angle-down ml-2"></span>
          </div>
        </span>
      </a>
      <div class="dropdown-menu" aria-labelledby="account_settings">
        <a class="dropdown-item" href="javascript:void(0)" id="manage_account"><i class="fa fa-cog"></i> Manage Account</a>
        <a class="dropdown-item" href="ajax.php?action=logout"><i class="fa fa-power-off"></i> Logout</a>
      </div>
    </li>
  </ul>
</nav>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function updateNotificationCount() {
  $.ajax({
    url: './admin/get_notifications.php',
    method: 'GET',
    dataType: 'json',
    success: function(response) {
      let notifDropdown = $('#notif-dropdown');
      notifDropdown.find('.notif-item').remove(); // Clear old notifications

      if (response.evaluations.length > 0) {
        response.evaluations.forEach(evaluation => {
          let timestamp = evaluation.timestamp;
          let message = `<strong>Evaluation Done</strong><br><strong>Submitted:</strong> ${timestamp}`;
          notifDropdown.prepend(`<div class="dropdown-item notif-item">${message}</div>`);
        });

        // Show count only if there are new notifications
        let count = response.evaluations.length;
        if (count > 0) {
          $('#notif-count').text(count).show();
        }
      } else {
        $('#notif-count').hide();
      }
    }
  });
}

$(document).ready(function() {
  updateNotificationCount(); // Load notifications on page load

  $('#notif-btn').click(function() {
    // Hide the count, but don't remove notifications from the dropdown
    $('#notif-count').hide();

    // Mark notifications as seen
    $.ajax({
      url: './admin/mark_notifications_seen.php',
      method: 'POST',
      success: function() {
        console.log("Notifications marked as seen.");
      }
    });
  });
});

// Refresh notifications every 10 seconds
setInterval(updateNotificationCount, 10000);
</script>


