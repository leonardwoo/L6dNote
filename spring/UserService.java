
import org.springframework.security.core.userdetails.UserDetails;
import org.springframework.security.core.userdetails.UserDetailsService;
import org.springframework.security.core.userdetails.UsernameNotFoundException;
import org.springframework.stereotype.Service;

/**
 * 
 */
@Service
public class UserService implements UserDetailsService {

  @Autowire
  private UserMapper mapper;
  
  @Override
  public UserDetails loadUserByUsername(String username) throws UsernameNotFoundException {
    User user = mapper.queryByUsername(username);
    return new UserPrincipal(user);
  }

}
