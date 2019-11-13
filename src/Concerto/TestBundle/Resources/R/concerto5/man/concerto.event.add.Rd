\name{concerto.event.add}
\alias{concerto.event.add}
\title{
    Events
}
\description{
    Adding events.
}
\usage{
    concerto.event.add(name, fun)
}
\arguments{
    \item{name}{
        Event name
    }
    \item{fun}{
        Function
    }
}
\details{
}
\references{
}
\author{
    Przemyslaw Lis
}
\note{
}

\seealso{
}
\examples{
    concerto.event.add("onTemplateSubmit", function(response) {
        print(response)
    })
}

\keyword{events}
