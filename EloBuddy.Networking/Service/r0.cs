using System;
using System.CodeDom.Compiler;
using System.ComponentModel;
using System.Diagnostics;
using System.Runtime.Serialization;

namespace EloBuddy.Networking.Service
{
    [DataContract(Name = "r0", Namespace = "http://schemas.datacontract.org/2004/07/")]
    [DebuggerStepThrough]
    [GeneratedCode("System.Runtime.Serialization", "3.0.0.0")]
    [KnownType(typeof(d0))]
    public class r0 : IExtensibleDataObject, INotifyPropertyChanged
    {
        protected void RaisePropertyChanged(string property)
        {
            this.propertyChanged(this, new PropertyChangedEventArgs(property));
        }

        public event PropertyChangedEventHandler PropertyChanged
        {
            add
            {
                this.propertyChanged += value;
            }
            remove
            {
                this.propertyChanged -= value;
            }
        }

        [OptionalField]
        private string BodyField;

        [NonSerialized]
        private ExtensionDataObject extensionDataField;

        private PropertyChangedEventHandler propertyChanged;

        [OptionalField]
        private bool SuccessField;

        [DataMember]
        public string Body
        {
            get
            {
                return this.BodyField;
            }
            set
            {
                this.BodyField = value;
            }
        }

        [DataMember]
        public bool Success
        {
            get
            {
                return this.SuccessField;
            }
            set
            {
                this.SuccessField = value;
            }
        }

        [Browsable(false)]
        public ExtensionDataObject ExtensionData
        {
            get
            {
                return this.extensionDataField;
            }
            set
            {
                this.extensionDataField = value;
            }
        }
    }
}
