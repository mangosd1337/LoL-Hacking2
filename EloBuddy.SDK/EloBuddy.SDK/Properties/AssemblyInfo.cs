using System.Reflection;
using System.Runtime.InteropServices;
using System.Security;

// General Information about an assembly is controlled through the following 
// set of attributes. Change these attribute values to modify the information
// associated with an assembly.

[assembly: AssemblyTitle("EloBuddy.SDK")]
[assembly: AssemblyDescription("EloBuddy Software Development Kit")]
[assembly: AssemblyConfiguration("")]
[assembly: AssemblyCompany("EloBuddy")]
[assembly: AssemblyProduct("EloBuddy.SDK")]
[assembly: AssemblyCopyright("Copyright © EloBuddy 2016")]
[assembly: AssemblyTrademark("EloBuddy")]
[assembly: AssemblyCulture("")]

// Allow addon access

[assembly: AllowPartiallyTrustedCallers]
[assembly: SecurityRules(SecurityRuleSet.Level1, SkipVerificationInFullTrust = true)]

// Setting ComVisible to false makes the types in this assembly not visible 
// to COM components.  If you need to access a type in this assembly from 
// COM, set the ComVisible attribute to true on that type.

[assembly: ComVisible(false)]

// The following GUID is for the ID of the typelib if this project is exposed to COM

[assembly: Guid("6f5488f9-858c-471b-a21f-69bc1e768d9f")]

// Version information for an assembly consists of the following four values:
//
//      Major Version
//      Minor Version 
//      Build Number
//      Revision
//
// You can specify all the values or you can default the Build and Revision Numbers 
// by using the '*' as shown below:
// [assembly: AssemblyVersion("1.0.*")]

[assembly: AssemblyVersion("1.0.0.0")]
[assembly: AssemblyFileVersion("1.0.0.0")]
