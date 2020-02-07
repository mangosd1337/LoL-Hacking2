using System.Reflection;
using System.Resources;
using System.Runtime.CompilerServices;
using System.Runtime.InteropServices;
using System.Security;

// General Information about an assembly is controlled through the following 
// set of attributes. Change these attribute values to modify the information
// associated with an assembly.

[assembly: AssemblyTitle("EloBuddy.Sandbox")]
[assembly: AssemblyDescription("Safe environment sandbox for addons.")]
[assembly: AssemblyConfiguration("")]
[assembly: AssemblyCompany("EloBuddy")]
[assembly: AssemblyProduct("EloBuddy.Sandbox")]
[assembly: AssemblyCopyright("Copyright © EloBuddy 2016")]
[assembly: AssemblyTrademark("EloBuddy")]
[assembly: AssemblyCulture("")]
[assembly: NeutralResourcesLanguage("en")]

// Allow addon access

[assembly: AllowPartiallyTrustedCallers]
[assembly: SecurityRules(SecurityRuleSet.Level1)]

// Allow SDK internal access

[assembly:
    InternalsVisibleTo(
        "EloBuddy.SDK,PublicKey=002400000480000094000000060200000024000052534131000400000100010011894ad95b5a75e3de542809565b3761abb6280214b785efc8facfb678c161e534700b9c0c79c1e790325d7e9f599a5bf645024db9d49bff4e165bc58b561b0ca861b617dce4947462da6c141120c2f2aa3a131cb8acdfa3fdbbd6b0120773a71c1d04eab9e2f2b9b360333ed5eda9b91869445705a2669585dc8d94ea4266bd"
        )]

// Setting ComVisible to false makes the types in this assembly not visible 
// to COM components.  If you need to access a type in this assembly from 
// COM, set the ComVisible attribute to true on that type.

[assembly: ComVisible(false)]

// The following GUID is for the ID of the typelib if this project is exposed to COM

[assembly: Guid("5862c26d-ec8a-45ac-a080-779bfe0d4801")]

// Version information for an assembly consists of the following four values:
//
//      Major Version
//      Minor Version 
//      Build Number
//      Revision
//
// You can specify all the values or you can default the Build and Revision Numbers 
// by using the '*' as shown below:

[assembly: AssemblyVersion("1.0.*")]

// [assembly: AssemblyVersion("1.0.0.0")]
// [assembly: AssemblyFileVersion("1.0.0.0")]
